#!/usr/bin/env python3
"""Process-skill benchmark runner (harness-agnostic).

Runs the evals in evals.json against the skills installed in .claude/skills/,
grades each run against live ground truth, and writes a dated results dir.

Usage (from the repo root):
    python3 .claude/skills-bench/run_bench.py                 # all evals, with-skill only
    python3 .claude/skills-bench/run_bench.py --evals 0,1,4   # subset (after editing those skills)
    python3 .claude/skills-bench/run_bench.py --baseline      # also run skill-stripped baselines
    CLAUDE_BENCH_FLAGS="--permission-mode acceptEdits" ...    # override claude flags

Requirements: `claude` CLI on PATH, gh authenticated (for PR ground-truth checks).
Cost note: each eval run is a full Haiku agent session; a full with-skill pass is
one run + one grading call per eval. Run --baseline only when you changed something
big enough to want the delta re-measured.

Skills are prompts. The point of running on Haiku is that our processes must survive
lighter models — do NOT "fix" a failing eval by switching to a bigger model.
"""

import argparse
import json
import shutil
import subprocess
import sys
import tempfile
import time
from datetime import datetime
from pathlib import Path

BENCH_DIR = Path(__file__).resolve().parent
REPO = BENCH_DIR.parent.parent  # .claude/skills-bench -> repo root
# acceptEdits alone does NOT auto-allow `Write` of new files in headless -p runs,
# so every write-deliverable eval was silently failing with an empty outputs dir
# (found on the bench's first real execution). Allow Write explicitly; worktree
# isolation + the never-push instructions in the prompts stay the guardrails.
DEFAULT_FLAGS = "--permission-mode acceptEdits --allowedTools Write"
GRADER_MODEL = "claude-sonnet-5"


def sh(cmd, cwd=REPO, timeout=120, check=False):
    return subprocess.run(cmd, shell=True, cwd=cwd, timeout=timeout,
                          capture_output=True, text=True, check=check)


def claude_p(prompt, model, cwd, flags, timeout=900):
    r = subprocess.run(
        ["claude", "-p", prompt, "--model", model, *flags.split()],
        cwd=cwd, timeout=timeout, capture_output=True, text=True)
    return r.stdout.strip(), r.returncode


def make_worktree(tag, strip_skill=None):
    wt = REPO.parent / f"wt-bench-{tag}-{int(time.time())}"
    sh(f"git worktree add --detach {wt} HEAD", check=True)
    if strip_skill:
        shutil.rmtree(wt / ".claude" / "skills" / strip_skill, ignore_errors=True)
    return wt


def drop_worktree(wt):
    sh(f"git worktree remove --force {wt}")


def run_eval(ev, config, out_root, model, flags):
    out = out_root / f"eval-{ev['id']}-{ev['eval_name']}" / config / "run-1" / "outputs"
    out.mkdir(parents=True, exist_ok=True)
    # Agents write to a NEUTRAL temp dir, not the results tree: harness sensitivity
    # rules refuse agent writes under .claude/** even with --allowedTools Write, so
    # {OUT} pointed inside the bench dir yields an empty outputs dir every time. The
    # runner archives the temp outputs into the results tree afterwards (below).
    agent_out = Path(tempfile.mkdtemp(prefix=f"bench-out-e{ev['id']}-"))
    prompt = ev["prompt"].replace("{OUT}", str(agent_out))
    skill_dir = REPO / ".claude" / "skills" / ev["skill"]

    wt = None
    if config == "with_skill":
        prompt = (f"FIRST read the skill at {skill_dir}/SKILL.md and follow it "
                  f"while performing the task.\n\n{prompt}")
        if ev.get("isolation"):
            wt = make_worktree(f"e{ev['id']}s")
    else:
        # Baseline must not see the skill under test: run from a stripped worktree.
        wt = make_worktree(f"e{ev['id']}b", strip_skill=ev["skill"])
    cwd = wt or REPO

    print(f"  running eval-{ev['id']} {config} ...", flush=True)
    stdout, rc = claude_p(prompt, model, cwd, flags)
    (out.parent / "final-message.txt").write_text(stdout or f"(exit {rc}, no output)")

    # Archive the agent's temp outputs into the results tree for grading + record.
    for f in sorted(agent_out.rglob("*")):
        if f.is_file():
            dest = out / f.relative_to(agent_out)
            dest.parent.mkdir(parents=True, exist_ok=True)
            dest.write_bytes(f.read_bytes())
    shutil.rmtree(agent_out, ignore_errors=True)

    if wt:
        info = sh("git log --format='%h %s%n%b' HEAD@{u}..HEAD 2>/dev/null; echo '=== files ==='; "
                  "git diff --name-status HEAD", cwd=wt)
        (out / "commit-info.txt").write_text(info.stdout)
        drop_worktree(wt)
    return out


def grade(ev, out, flags):
    gt = "\n".join(sh(c, timeout=60).stdout.strip() for c in ev["ground_truth_check"])
    files = {}
    for f in sorted(out.rglob("*")):
        if f.is_file() and f.stat().st_size < 40_000:
            files[str(f.relative_to(out))] = f.read_text(errors="replace")
    grader_prompt = f"""You are grading one benchmark run of an AI agent. Judge STRICTLY against the ground truth below (it was just measured live - trust it over your priors and over anything the graded report claims).

GROUND TRUTH (live output of the eval's ground_truth_check commands):
{gt}

ASSERTIONS to evaluate (pass/fail each; no partial credit):
{json.dumps(ev['assertions'], indent=2)}

RUN OUTPUTS (filename -> content):
{json.dumps(files, indent=2)[:60_000]}

Reply with ONLY a JSON object, no markdown fences:
{{"expectations": [{{"text": "<assertion verbatim>", "passed": true, "evidence": "<short quote/observation>"}}]}}"""
    stdout, _ = claude_p(grader_prompt, GRADER_MODEL, REPO, "--permission-mode plan", timeout=300)
    try:
        g = json.loads(stdout[stdout.index("{"):stdout.rindex("}") + 1])
    except (ValueError, json.JSONDecodeError):
        g = {"expectations": [{"text": a, "passed": False,
                               "evidence": "GRADER PARSE FAILURE - regrade manually"}
                              for a in ev["assertions"]]}
    p = sum(1 for e in g["expectations"] if e.get("passed"))
    g["summary"] = {"passed": p, "failed": len(g["expectations"]) - p,
                    "total": len(g["expectations"]),
                    "pass_rate": round(p / max(len(g["expectations"]), 1), 4)}
    (out.parent / "grading.json").write_text(json.dumps(g, indent=2))
    return g["summary"]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--evals", help="comma-separated eval ids (default: all)")
    ap.add_argument("--baseline", action="store_true", help="also run skill-stripped baselines")
    ap.add_argument("--model", help="override eval model")
    ap.add_argument("--flags", default=None, help="override claude flags")
    args = ap.parse_args()

    spec = json.loads((BENCH_DIR / "evals.json").read_text())
    model = args.model or spec["model"]
    import os
    flags = args.flags or os.environ.get("CLAUDE_BENCH_FLAGS", DEFAULT_FLAGS)
    ids = {int(x) for x in args.evals.split(",")} if args.evals else None
    evals = [e for e in spec["evals"] if ids is None or e["id"] in ids]

    out_root = BENCH_DIR / "results" / datetime.now().strftime("%Y-%m-%d-%H%M")
    out_root.mkdir(parents=True)
    print(f"benchmark -> {out_root}  (model {model}, {len(evals)} evals, "
          f"baseline={'yes' if args.baseline else 'no'})")

    rows = []
    for ev in evals:
        configs = ["with_skill"] + (["without_skill"] if args.baseline else [])
        for cfg in configs:
            out = run_eval(ev, cfg, out_root, model, flags)
            s = grade(ev, out, flags)
            rows.append((ev["eval_name"], cfg, s["passed"], s["total"]))
            print(f"    -> {s['passed']}/{s['total']}")

    lines = ["# Skill benchmark — " + out_root.name, "",
             "| eval | config | passed |", "|---|---|---|"]
    lines += [f"| {n} | {c} | {p}/{t} |" for n, c, p, t in rows]
    tot_p, tot_t = sum(r[2] for r in rows), sum(r[3] for r in rows)
    lines += ["", f"**Total: {tot_p}/{tot_t} ({tot_p / max(tot_t, 1):.0%})**"]
    (out_root / "benchmark.md").write_text("\n".join(lines) + "\n")
    print("\n".join(lines))
    print(f"\nDetails: {out_root}/  — compare against the previous dated dir before trusting a skill edit.")


if __name__ == "__main__":
    sys.exit(main())

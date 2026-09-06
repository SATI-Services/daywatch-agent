---
name: skill-bench
description: Run the checked-in skill benchmark whenever a skill in .claude/skills/ is created, edited, or imported, before committing the change — and whenever anyone asks to "benchmark the skills", "rerun the skill evals", or wonders whether a skill still works on lighter models. Skill edits are prompt changes with no compiler; this benchmark is their only regression test, so do not commit a skill change without running the affected evals.
---

# Skill Bench — regression tests for our skills

> Generic process skill. The runner lives in `.claude/skills-bench/`. It is harness-aware but
> project-agnostic: the evals in `evals.json` are what you point at your project's real state.

Skills are prompts, and prompt edits fail silently: an innocuous rewording can stop a lighter model
from following the procedure, and nothing tells you until an agent quietly trusts a stale doc again.
The benchmark in `.claude/skills-bench/` is the regression suite. Treat a skill edit like a code
change: run the affected evals before committing.

## When to run

- **After creating or editing any skill** in `.claude/skills/` → run that skill's evals (see the
  `skill` field per eval in `.claude/skills-bench/evals.json`).
- **On request** ("benchmark the skills", "are the skills still working?") → full run.
- **Periodically** (monthly, or when the repo shifts a lot) → full run; ground truths are
  re-measured live so repo drift is handled, but eval *prompts* can rot — flag any eval whose
  premise no longer exists.

## How to run

From the repo root:

```bash
python3 .claude/skills-bench/run_bench.py                # all evals, with-skill only (the default regression pass)
python3 .claude/skills-bench/run_bench.py --evals 0,1    # just the evals for the skill you touched
python3 .claude/skills-bench/run_bench.py --baseline     # adds skill-stripped baseline runs (the ± delta) — costlier; use when the value of a skill itself is in question
```

Needs the `claude` CLI on PATH and `gh` authenticated (for PR ground-truth checks). Eval agents run
on **Haiku by design** — the whole point is that our processes must survive lighter models; do not
"fix" a failure by re-running on a bigger model.

## Reading the results

Results land in `.claude/skills-bench/results/<date>/` (`benchmark.md` + per-run `grading.json` with
evidence).

- **Compare against the previous dated results dir** — a pass rate means little without the trend.
  The previous dir is the baseline your edit must not regress.
- Ground truth is measured live at grading time (the `ground_truth_check` commands), so an expected
  answer changing because the repo changed is handled — but if an eval's *premise* is gone (the doc
  it references was archived), the eval needs updating, not the skill. Update `evals.json` in the
  same commit as the skill change and say so.
- A with-skill failure = the skill regressed (or the eval rotted). Fix before committing; if you
  must ship anyway, record the known failure in your session log.

## Adding evals

When a new skill lands (or a new failure mode is discovered in the wild — the best evals are
post-mortems), add an eval to `evals.json`: a realistic prompt, `ground_truth_check` commands that
measure the truth at grading time, and assertions phrased against that output rather than frozen
answers. **Trap-style evals** (where the naive instruction is the wrong move, like the write-partition
trap) discriminate best — a weak model takes the bait, a skill-following one doesn't.

## Cost & safety

- A full with-skill pass ≈ one Haiku session per eval + a Sonnet grading call each; a `--baseline`
  pass doubles the agent runs.
- Write-evals run in throwaway git worktrees and are instructed never to push; baseline runs use a
  worktree with the skill under test stripped so they can't crib from it.
- The runner never touches your checkout's `main`.

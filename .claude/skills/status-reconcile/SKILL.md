---
name: status-reconcile
description: Verify any claim about work state (shipped, merged, dropped, in-flight, PR open, branch deleted) against git/GitHub reality before recording or acting on it. Use this whenever you are about to write "shipped"/"merged"/"done" into any session doc, whenever a status file or audit disagrees with what you see in code, whenever a branch or PR has disappeared, when reconciling backlogs or status files, or when someone asks "did X land?" / "is X on main?". Session docs are claims, not truth — always run this skill's checks before trusting or updating them.
---

# Status Reconcile — docs are claims, git is truth

> Generic process skill. `SATI-Services/daywatch-agent` = your GitHub slug; `main` = your trunk branch
> (some repos call it `master`); ``src/ config/`` = where features live (e.g. `routes/ app/
> resources/` on Laravel, `artifacts/ lib/` on a JS monorepo). The precedent stories are real —
> keep them, they teach the failure mode better than an abstraction would.

## Why this exists

On the reference project a finished, working feature was **silently lost** through three
individually-reasonable steps: an audit recorded it as *"Shipped (PR open)"*; the remote branch
was later deleted un-merged; the author saw the deletion and treated it as an intentional close.
Every agent trusted a document written by the previous agent, and no one checked git. The shared
knowledge base gets bigger and staler every week — the *only* stable ground truth is
`origin/main` and GitHub's PR records. This skill is the checklist that keeps them authoritative.

## The evidence hierarchy

When sources disagree, higher beats lower — no exceptions:

1. **`origin/main` content** (does the route/class/view/module exist there *now*?)
2. **GitHub PR state** (`gh pr view` — MERGED / CLOSED / OPEN)
3. **git history** (ancestry, reflogs, branch tips)
4. **Session docs** (`SHARED.md`, `status/*.md`, audit logs, notes) — *claims with a timestamp*
5. **Commit messages / branch names** — *intentions, not outcomes*

Always `git fetch origin` before any verdict — a stale local `origin/main` produces
confidently wrong answers.

## The truth table

Find the claim you're checking; run the command; record the verdict with its evidence string.

### "X is shipped / done / on main"

Prefer **feature presence** over commit archaeology (commits get squashed, reworded, split):

```bash
git fetch origin
# does the artifact exist on origin/main? (route, controller, view, module, config key…)
git grep -n "<route-or-symbol-or-path>" origin/main -- `src/ config/`
```

- Found → **SHIPPED**. Evidence: `origin/main:<file>:<line>`.
- Not found → **NOT SHIPPED**, regardless of what any doc says. Keep digging with the checks below
  to find out *why* (in-flight? lost? renamed?).

If you have a commit hash: `git merge-base --is-ancestor <sha> origin/main && echo shipped || echo not-on-main`.
A hash that is an ancestor proves that *commit* landed — still grep for the artifact if the claim
is about a *feature* (it may have been reverted later).

**Renames**: before declaring a feature missing, check whether it shipped under another name — read
what the source feature actually *does* (page title, API endpoints it calls) and grep for those.
Precedent: a `reports/drhook` page shipped renamed as `reports/engagement-analysis`; agents
repeatedly re-flagged it as a gap because they searched the old name.

### "PR #N is open" (found in a doc)

```bash
gh pr view <N> --repo SATI-Services/daywatch-agent --json state,mergedAt,mergeCommit,headRefName
```

- `MERGED` → SHIPPED (evidence: PR #N + merge commit).
- `OPEN` → **IN-FLIGHT**. *"PR open" is never "shipped" — do not copy it into a Shipped section.*
- `CLOSED` (no mergedAt) → **CLOSED-UNMERGED** → treat as potentially LOST (next section).

### "The branch was deleted" / branch is missing

A deleted branch is **an observation, not a verdict**. It means merged-and-cleaned OR
closed-and-abandoned OR housekeeping-by-mistake. Distinguish:

```bash
gh pr list --repo SATI-Services/daywatch-agent --head <branch> --state all \
  --json number,state,mergedAt,title
```

- PR MERGED → fine; the work is in. Verify with a feature-presence grep anyway.
- PR CLOSED un-merged → **LOST** — a finished-or-partial feature fell out of the pipeline. Never
  treat this as "intentionally closed" unless you find a *recorded decision* (a session doc that
  says who decided and why). Deletion itself is not a decision.
- No PR ever existed → the work never entered review; ask the owner before assuming anything.

### "It's in flight" / status file says someone is working on X

Check the branch is real and moving: `git ls-remote origin '<author>/*'` and compare tip dates
(`git log -1 --format='%ci' origin/<branch>`). A months-old tip contradicting an "in flight" claim
is **STALE** — flag it, don't repeat it.

## Verdict vocabulary

Use exactly these words in reports and doc corrections, each with an evidence string the next agent
can re-verify in one command:

| Verdict | Meaning | Evidence to record |
|---|---|---|
| SHIPPED | artifact present on `origin/main` | `origin/main:<file>:<line>` or PR # + merge sha |
| IN-FLIGHT | open PR / active branch | PR # or branch + tip sha + tip date |
| LOST | closed/deleted un-merged, no recorded decision | PR # + state, or branch deletion evidence |
| DROPPED | un-merged **with** a recorded decision | link to the decision doc |
| STALE | doc claim contradicted by git | both: the claim + the git evidence |
| UNKNOWN | can't verify (no gh, ambiguous name) | what you tried; never guess |

## When docs and git disagree

1. **Git wins.** Update the doc *in the same session* — a known-wrong doc left standing is how the
   next agent gets poisoned.
2. Correct it minimally and visibly: keep the original claim, append the correction with date +
   evidence (e.g. *"~~Shipped~~ **STALE 2026-07-02**: PR #N closed un-merged — see `gh pr view N`"*).
   Follow the write-partition rule: fix `SHARED.md`/your own files directly; for someone else's
   `status/<author>.md`, leave a note instead (see the `session-docs` skill).
3. **LOST features escalate, never self-resolve.** Write a note to the owner + lead proposing
   revive-or-drop. Do not unilaterally revive (you may not know why it stalled) and do not
   unilaterally record it as dropped (that's how it got lost the first time).

## Recording rules (stop the next agent's confusion at the source)

- Every state you write into a session doc carries **evidence**: a sha, a PR number, or a
  `file:line` on `origin/main`. Unevidenced claims are what this skill exists to clean up.
- Write dates absolute (`2026-07-02`), never "today"/"last week".
- "PR open" belongs in *In flight*, not *Shipped*. Move items only on a MERGED/presence verdict.

## Reporting

Open every reconcile answer with the verdict line (e.g. `**SHIPPED** — PR #140 merged 2026-07-01,
595ea965`), then the evidence. And **run the checks yourself** — never hand the reader a list of
commands to go run when you have the tools to execute them; the deliverable is a verdict, not homework.

## Sweep procedure (reconciling a whole status file or audit)

1. `git fetch origin` once.
2. Extract every checkable claim (shipped / PR open / branch / in-flight).
3. Run the truth-table check for each; note verdicts + evidence.
4. Produce the report table: `| claim (doc:line) | verdict | evidence | doc fix |`.
5. Apply doc fixes you're allowed to make (write-partition); notes for the rest.
6. Anything LOST → escalation note immediately, in the same session.

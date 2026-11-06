---
title: Track A2 is worktree-per-task — the parallel-safe default
tags: [git, workflow, worktree]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: c67a73d16 2026-11-06
---
**A2 — worktree-per-task (parallel-safe default):**


git fetch origin
git worktree add -b <author>/<topic> ../wt-<topic> origin/main
cd ../wt-<topic> && composer install   # needs PHP ≥ 8.3 for the Pest 4.x dev tooling
# …edit, commit…
git fetch origin && git merge origin/main      # catch up; NEVER rebase main
git push origin HEAD:main
git worktree remove ../wt-<topic>

Collision risk scales with concurrent *sessions*, not headcount — two of your own agents running at once race on the same hot file exactly as two people would, so A2 is the default even solo.

**Why:** Your primary checkout's `main` never carries an un-pushed commit, so it can't diverge.

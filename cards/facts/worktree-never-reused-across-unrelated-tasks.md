---
title: Never reuse a worktree across unrelated tasks
tags: [git, worktree, hygiene]
status: verified 2026-11-07 auto
source: [docs/WORKFLOW.md]
as_of: 34b58366e 2026-11-06
---
In Track A2 (worktree-per-task), keep one worktree per task: don't reuse a worktree across unrelated tasks — start the task with its own `git worktree add -b <author>/<topic> ../wt-<topic> origin/main`. Track A1 (direct-on-`main` for small reversible changes) stays legal and uses no worktree.

**Why:** The "Worktree hygiene (or you collide with yourself)" block of docs/WORKFLOW.md — reusing a checkout is how a session collides with itself.

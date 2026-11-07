---
title: Remove a worktree whose branch has already merged to main
tags: [git, worktree, hygiene]
status: verified 2026-11-07 auto
source: [docs/WORKFLOW.md]
as_of: 34b58366e 2026-11-06
---
If a worktree's branch has already merged to `main`, remove the worktree.

**Why:** A lingering worktree on a dead branch is where Track A/B confusion starts (docs/WORKFLOW.md).

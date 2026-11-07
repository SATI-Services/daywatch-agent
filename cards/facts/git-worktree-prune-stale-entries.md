---
title: Run git worktree prune to clear stale worktree entries
tags: [git, worktree, hygiene]
status: verified 2026-11-07 auto
source: [docs/WORKFLOW.md]
as_of: 34b58366e 2026-11-06
---
Run `git worktree prune` to clear stale worktree entries.

**Why:** Stale entries on the primary checkout risk divergence from origin/main, contributing to checkout clutter and confusion.

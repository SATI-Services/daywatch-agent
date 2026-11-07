---
title: One worktree per task; never run two agent sessions in the same checkout
tags: [git, worktree, collision]
status: verified 2026-11-07 auto
source: [docs/WORKFLOW.md]
as_of: 34b58366e 2026-11-06
---
One worktree per task. **Never run two agent sessions in the same checkout** — they share a working tree and index and will clobber each other's staged changes.

**Why:** This is the "it conflicted with itself three times this week" failure. Collision risk scales with concurrent *sessions*, not headcount — two of your own agents running at once race on the same hot file exactly as two people would.

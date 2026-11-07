---
title: Remove a task's worktree in the same session when the task is done
tags: [git, worktree, hygiene]
status: verified 2026-11-07 auto
source: [docs/WORKFLOW.md]
as_of: 34b58366e 2026-11-06
---
One task done → `git worktree remove ../wt-<topic>` in the same session; the Track A2 recipe ends with this step.

**Why:** Part of the "Worktree hygiene (or you collide with yourself)" block in docs/WORKFLOW.md — the worktree's lifetime is the task's lifetime.

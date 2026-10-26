---
title: Concurrent agents of the same person work on Track A2 worktrees
tags: [session-logs, worktrees, agents]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
Concurrent agents of the same person should be on Track A2 worktrees: disjoint sections merge cleanly there, and that — not per-agent filenames — is what prevents file races on the shared `status/<author>.md`.

**Why:** One person's agents all write the same status file; without worktrees their concurrent sessions race it.

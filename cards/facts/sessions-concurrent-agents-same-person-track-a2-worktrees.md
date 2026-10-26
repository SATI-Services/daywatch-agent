---
title: Concurrent agents of the same person run on Track A2 worktrees
tags: [session-logs, worktrees, collaboration]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
Concurrent agents of the same person should be on Track A2 worktrees: each edits a disjoint `## <harness>` section of `status/<author>.md`, and disjoint sections merge cleanly. That — not per-agent filenames — is what prevents file races between one person's agents.

**Why:** Per-agent filenames would fragment the person's canonical status; worktrees keep one file per author while letting simultaneous agents merge without conflict.

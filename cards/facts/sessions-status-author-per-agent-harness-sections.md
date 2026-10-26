---
title: Per-agent ## <harness> sections in status/<author>.md; each agent edits only its own section
tags: [session-logs, shared-memory, collaboration]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
When more than one agent works for the same person, each agent writes under its own `## <harness>` section in that person's `status/<author>.md` (the fixed schema applies inside the section) and edits only its own section — never another agent's or the operator's. Concurrent agents of the same person should be on Track A2 worktrees: disjoint sections merge cleanly, and that — not per-agent filenames — is what prevents file races.

**Why:** `<author>` is always the person from git identity, so one person's concurrent agents share a single status file; per-agent sections plus Track A2 worktrees keep their writes disjoint.

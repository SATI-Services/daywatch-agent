---
title: Sessions author is the person, from git identity, not the harness
tags: [session-logs, collaboration]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: d4813a7e6 2026-10-25
---
`status/<author>.md` is owned by the person — `<author>` is the git identity (`will`, `josh`, …), whichever harness or agent drove the work; the work is canonically theirs. Agents identify themselves *inside* the file: the `**Agent:**` field in dated logs, and — only when more than one agent works for the same person — a per-agent `## <harness>` section in `status/<author>.md` (same fixed schema inside it); each agent edits only its own section, never another agent's or the operator's. Disjoint per-agent sections in one file — not per-agent filenames — are what prevents file races.

**Why:** Work stays attributed to the person while concurrent agents of that person merge cleanly without file-level races.

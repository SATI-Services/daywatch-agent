---
title: Session state partitioned by author — writes never collide; everyone reads everything
tags: [sessions, partition, write-safety]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: 20dd42e0f 2026-11-06
---
State is **partitioned by author so writes never collide; everyone reads everything.** `<author>` is always the person (from git identity), whichever harness drove the work — agents stamp their harness **inside** the files: the `**Agent:** <harness> (<model>)` field in dated logs and a per-agent `## <harness>` section in `status/<author>.md`, each agent editing only its own section. Never leave the model implied by the harness.

- `SHARED.md` — thin cross-cutting state (≤ ~50 lines, append-mostly).
- `status/<author>.md` — your live WIP; you only ever write your own. Fixed schema: `## In flight` · `## Next up` (ordered backlog) · `## Open follow-ups` · `## Shipped` (evidenced). Keep ≤ ~80 lines.
- `notes/` — inter-author inbox; action then archive.
- `<author>/YYYY-MM-DD-*.md` — dated logs (full archive).

**Why:** Write-partition stops collisions; everyone reading stops duplicate work.

---

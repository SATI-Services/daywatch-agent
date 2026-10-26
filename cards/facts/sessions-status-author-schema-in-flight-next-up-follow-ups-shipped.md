---
title: status/<author>.md uses a fixed four-section schema, capped at ~80 lines
tags: [session-logs, collaboration, backlog]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: d4813a7e6 2026-10-25
---
`status/<author>.md` holds one author's live work; `<author>` is always the person from git identity (`will`, `josh`, …), not the harness. Fixed schema:
- `## In flight` — ≤2 items, what you're doing now.
- `## Next up` — ordered backlog, top = do-next; the single deterministic answer to "what should I work on next?".
- `## Open follow-ups` — unscheduled work.
- `## Shipped` — rolling ticker, ~8 most-recent or ~14 days, each with evidence (sha, PR #, or trunk route); delete older entries. "PR open" is not shipped — it stays in `## In flight` until merged.

Keep the whole file ≤ ~80 lines; the SessionStart tick warns when it's over cap and only the author prunes. Work enters `## Next up` two ways: the author adds it, or a teammate drops a `notes/` note addressed to them, which the author triages. Concurrent agents of the same person each edit their own `## <harness>` section (same schema), never another's or the operator's.

**Why:** Write-partition prevents file races; the ordered backlog and drained inbox prevent duplicate work.

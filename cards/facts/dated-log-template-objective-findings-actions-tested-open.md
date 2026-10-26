---
title: Dated log template: Objective, Key findings, Actions taken, Tested against, Open items
tags: [session-logs, template]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: d4813a7e6 2026-10-25
---
Dated log template (`docs/sessions/<author>/YYYY-MM-DD-*.md`) has a fixed structure; update logs as you go, not at the end:
- `# Session: <title>`
- `**Date:** YYYY-MM-DD   **Author:** <author>   **Agent:** <harness>   **Track:** A (main) | B (branch <name>)`
- `## Objective` — what we set out to do
- `## Key findings` — discoveries, root causes, decisions (with the why)
- `## Actions taken` — code/config changes, follow-ups; reference commit hashes
- `## Tested against` — which environment, real data or fixtures, which suites, green or not
- `## Open items` — anything unresolved; mirror the live ones into `status/<you>.md` (or `SHARED.md` if cross-cutting)

**Why:** Git captures the what; the logs are the only place the why and the tested-against survive. Written as you go a log is two minutes; reconstructed from a diff later it is a morning and often wrong.

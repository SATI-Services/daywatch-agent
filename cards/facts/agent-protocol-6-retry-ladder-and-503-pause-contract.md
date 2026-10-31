---
title: Retry ladder and 503 stop/pause contract live in agent-protocol.md §6
tags: [daemon, dispatch, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: da814d4f7 2026-10-31
---
The daemon's retry ladder and the `503` `stop` NullBuffer pause contract are specified in `system/agent-protocol.md` §6.

**Why:** The retry/503 pause contract is cross-repo wire contract — it changes only docs-first via the `daywatch-payloads` skill, never package-locally.

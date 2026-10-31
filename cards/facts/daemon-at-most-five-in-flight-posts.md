---
title: Daemon keeps at most 5 ingest POSTs in flight
tags: [daemon, dispatch, limits]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
The daemon allows at most 5 POSTs in flight at once.

**Why:** The retry ladder and the 503 `stop` NullBuffer pause contract for those sends live in `system/agent-protocol.md` §6.

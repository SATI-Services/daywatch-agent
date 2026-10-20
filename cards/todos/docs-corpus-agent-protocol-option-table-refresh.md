---
title: Refresh docs corpus agent-protocol.md §7 option table with new sensors block and per-sensor sample
tags: [docs, corpus]
status: open
owner: ryan
since: 2026-10-12
source: [docs/sessions/status/ryan.md]
as_of: 6866d739e 2026-10-12
---
The option table in `agent-protocol.md` §7 is stale: it lacks the new `sensors` block (per-sensor `enabled`/`ignore`/`groups`), the M4 filters it replaced (`ignore_cache_keys`, `ignore_query_patterns`, `ignore_job_names`), and the 2026-10-12 per-sensor `sample` option (seven `DAYWATCH_*_SAMPLE_RATE` env vars). Refresh in the daywatch-mcp repo when next touching the corpus.

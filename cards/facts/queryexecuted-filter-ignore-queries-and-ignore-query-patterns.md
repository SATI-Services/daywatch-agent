---
title: QuerySensor filters via ignore_queries (drop stream) and ignore_query_patterns (default framework tables)
tags: [filtering, query-sensor, config]
status: verified 2026-10-23 auto
source: [docs/sessions/ryan/2026-10-06-filtering-jobs-and-queries.md]
as_of: bd5f9eb69 2026-10-23
---
QuerySensor reads `filtering.ignore_queries` (drop the whole stream) and `filtering.ignore_query_patterns` (default `*jobs*,*cache*,*sessions*,*batches*`, env `DAYWATCH_IGNORE_QUERY_PATTERNS`). Filter runs before buffering; ignored queries don't bump the `queries` counter. Plain needles match any SQL quoting (`"jobs"`, `` `jobs` ``, `[jobs]`).

**Why:** Framework internal tables are high-volume noise; `*cache*` subsumes `cache_locks` via single needle matching.

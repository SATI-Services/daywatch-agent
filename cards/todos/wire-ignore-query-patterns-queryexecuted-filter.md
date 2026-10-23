---
title: Wire ignore_queries and ignore_query_patterns to filter QueryExecuted events
tags: [sensor, filtering, queue]
status: open
owner: unassigned
since: 2026-10-05
source: [docs/sessions/ryan/2026-10-05-query-filtering.md]
as_of: 40aed7ebf 2026-10-22
---
Implement pattern-based SQL filtering in QuerySensor::handle() to match CacheEventSensor's needle/glob discipline. Wire the reserved `ignore_queries` + new `ignore_query_patterns` config (Str::is() patterns, precompile mirroring cache filter) to drop database queue poll and other recurring queries.

Done = default patterns ship that cover both `"jobs"` (double-quote) and `` `jobs` `` (backtick) grammar variants, QuerySensor consults them first, and Ryan or another operator confirms database-queue noise is filtered without suppressing job-body queries.

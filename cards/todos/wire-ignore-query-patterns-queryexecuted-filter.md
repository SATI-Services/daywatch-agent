---
title: Wire ignore_queries and ignore_query_patterns to filter QueryExecuted events
tags: [sensor, filtering, queue]
status: open
owner: unassigned
since: 2026-10-05
source: [docs/sessions/ryan/2026-10-05-query-filtering.md]
as_of: 40aed7ebf 2026-10-22
---
Wire the reserved, declared-not-wired `ignore_queries` (`config/daywatch.php:49`) plus a new `ignore_query_patterns` (`Str::is()` patterns, needle/glob precompile mirroring `CacheEventSensor`) into `QuerySensor::handle()`, consulted first, to drop the `queue:work` database-queue poll query emitted on every loop iteration.

Gated: the session ends "awaiting Ryan's go-ahead before implementing" — get that before writing code.

Done = shipped default patterns cover both `"jobs"` (double-quote) and `` `jobs` `` (backtick) grammar variants, `QuerySensor` consults them first, and database-queue noise is filtered without suppressing job-body queries.

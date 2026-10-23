---
title: QuerySensor records every QueryExecuted; ignore_queries config is reserved, not wired
tags: [sensor, filtering, queue, draft-feature]
status: verified 2026-10-23 auto
source: [docs/sessions/ryan/2026-10-05-query-filtering.md]
as_of: 965dffcc0 2026-10-23
---
`QuerySensor::handle()` records every `QueryExecuted` unconditionally. `daywatch.filtering.ignore_queries` exists in `config/daywatch.php:49` but is declared-not-wired (reserved, M4) — its config comment says setting it today changes nothing. There is no pattern-based SQL ignore: no query analogue of `CacheEventSensor`'s needle/glob filter.

Database queue poll queries (`select * from "jobs" where "queue" = ? …` from `Illuminate\Queue\DatabaseQueue::getNextAvailableJob()`, on every `queue:work` loop iteration) are high-volume noise — the query-side twin of the `illuminate:queue:restart` cache-key noise that `ignore_cache_keys` already drops by default. Grammar quoting differs (`"jobs"` vs `` `jobs` ``), so any shipped default pattern must cover both.

The only live lever is `Daywatch::pause()/resume()`: `Core::write()` drops all records while paused (`src/Core.php:467`), so a host app can wrap the `database` queue connector so `DatabaseQueue::pop()` runs paused. Caveats: it also suppresses the reserve-`UPDATE` record when a job is claimed, and job-body queries are unaffected (pop returns before processing).

**Why:** With no per-sensor query filter, silencing `queue:work` poll noise requires that pause/resume wrapper, at the cost of the record for the reserve `UPDATE` and with no effect on job-body queries.

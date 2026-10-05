# 2026-10-05 — query filtering question (database-queue poll noise)

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## What happened

Ryan asked how to filter out collection of the Laravel database-queue poll
query — `select * from "jobs" where "queue" = ? and (("reserved_at" is null and
"available_at" <= ?) or ("reserved_at" <= ?))` — emitted by
`Illuminate\Queue\DatabaseQueue::getNextAvailableJob()` on every `queue:work`
loop iteration. This is the query-side twin of the `illuminate:queue:restart`
cache-key noise that `ignore_cache_keys` already drops by default.

## Findings (no code changed)

- `daywatch.filtering.ignore_queries` exists in `config/daywatch.php:49` but is
  **declared-not-wired** (reserved, M4): the config comment says setting it
  today changes nothing. `QuerySensor::handle()` records every `QueryExecuted`
  unconditionally; there is no pattern-based SQL ignore (no query analogue of
  `CacheEventSensor`'s needle/glob filter).
- The only live lever is `Daywatch::pause()/resume()` — `Core::write()` drops
  all records while paused (`src/Core.php:467`). A host app can wrap the
  `database` queue connector so `DatabaseQueue::pop()` runs paused. Caveats:
  also suppresses the reserve-`UPDATE` when a job is claimed; job-body queries
  are unaffected (pop returns before processing).
- Proper fix (M4): wire `ignore_queries` + a new `ignore_query_patterns`
  (`Str::is()` patterns, needle/glob precompile mirroring
  `CacheEventSensor`) consulted first in `QuerySensor::handle()`. Grammar
  quoting differs (`"jobs"` vs `` `jobs` ``), so any shipped default pattern
  must cover both. Awaiting Ryan's go-ahead before implementing.

## Commits

- (none — question/answer session)

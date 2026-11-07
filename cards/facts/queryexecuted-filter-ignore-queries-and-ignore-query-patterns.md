---
title: QuerySensor drops queries matching the daywatch.sensors.*.ignore patterns before buffering
tags: [filtering, queryexecuted]
status: verified 2026-11-07 auto
source: [src/Sensors/QuerySensor.php, src/Support/Patterns.php]
as_of: a80ce4d1c 2026-11-07
---
QuerySensor accepts a `daywatch.sensors.*.ignore` pattern list; raw SQL matching a pattern is dropped before anything is buffered, so ignored queries cost nothing downstream. Default patterns exclude framework housekeeping: `jobs`, `cache`/`cache_locks`, `sessions`, `batches` (plain `*table*` needles match any grammar quoting).

**Why:** Every worker and cache/session driver emits internal queries; filtering at ingestion removes noise and cost.

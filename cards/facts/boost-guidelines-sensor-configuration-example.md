---
title: Boost guidelines include per-sensor config examples for enabled, sample, and groups
tags: [docs, boost, config]
status: verified 2026-10-21 auto
source: [resources/boost/guidelines/core.blade.php]
as_of: 1d707a5df 2026-10-21
---
`resources/boost/guidelines/core.blade.php` includes consumer-facing `sensors` config block examples showing:
- Disabling a sensor (`QuerySensor` via `enabled: false` or `DAYWATCH_QUERIES_ENABLED=false`)
- Per-record sampling on CacheEventSensor (`sample` env-backed)
- Cache-key grouping via regex on CacheEventSensor (`groups` config-file only, first-match-wins)

`ignore` is named as an available per-sensor option but no example is given for it.

**Why:** Consumers learn how to tune collection per sensor type in one place.

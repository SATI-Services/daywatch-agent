---
title: Legacy filtering.ignore_* keys override sensor enabled config when present
tags: [sensors, config, backwards-compat]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
For CacheEventSensor, QuerySensor, and OutgoingRequestSensor, a legacy `daywatch.filtering.*` kill-switch from a pre-sensors-block published config still wins over `daywatch.sensors.{class}.enabled` when present. The exact keys are `daywatch.filtering.ignore_cache_events` (CacheEventSensor), `daywatch.filtering.ignore_queries` (QuerySensor), `daywatch.filtering.ignore_outgoing_requests` (OutgoingRequestSensor); `enabled()` returns `! (bool) config($legacy)`, so semantics invert: `ignore_*` = disabled. No other sensor has a legacy key. The package no longer ships these keys; they come only from the host's own config.

**Why:** Backwards compatibility with existing host-app configurations.

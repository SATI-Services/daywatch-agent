---
title: Legacy filtering.* kill-switch config from pre-sensors-block still wins when present
tags: [sensors, config, backwards-compat]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
For CacheEventSensor, QuerySensor, and OutgoingRequestSensor, a legacy `daywatch.filtering.*` key from pre-sensors-block config still wins when present — it overrides `daywatch.sensors.{class}.enabled`. The exact keys are `daywatch.filtering.ignore_cache_events`, `daywatch.filtering.ignore_queries`, and `daywatch.filtering.ignore_outgoing_requests`. Semantics invert: `ignore_*` = disabled. The package no longer ships these keys; they come only from the host's own config.

**Why:** Backwards compatibility with existing host-app configurations.

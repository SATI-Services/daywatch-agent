---
title: Each sensor is gated by daywatch.sensors.{class}.enabled, default true
tags: [sensors, config]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
`SensorManager::enabled()` reads `daywatch.sensors.{class}.enabled`, where `{class}` is the fully-qualified sensor class (default `true`). When it returns false the attach method returns early, so none of that sensor's listeners are registered.

**Legacy override:** for `CacheEventSensor`, `QuerySensor` and `OutgoingRequestSensor`, if `daywatch.filtering.ignore_cache_events` / `ignore_queries` / `ignore_outgoing_requests` exists in the host config it wins, and its sense is inverted (`true` = disabled). The package no longer ships those keys, so they can only come from the host's own config.

**Why:** Allows granular control over which sensors run; the legacy branch keeps older published host configs working.

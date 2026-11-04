---
title: Each sensor is gated by daywatch.sensors.{class}.enabled, default true
tags: [sensors, config]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: 961f8e274 2026-11-04
---
`SensorManager::enabled()` reads `daywatch.sensors.{class}.enabled`, where `{class}` is the fully-qualified sensor class (default `true`). When it returns false the attach method returns early, so none of that sensor's listeners are registered — with one exception, the queue pair.

`attachQueueSensors()` checks `QueuedJobSensor` and `JobAttemptSensor` independently: it returns only when both are disabled, and whenever either is enabled it still installs the `Queue::createPayloadUsing` job_id hook before attaching the enabled one's listeners.

**Legacy override:** for `CacheEventSensor`, `QuerySensor` and `OutgoingRequestSensor`, if `daywatch.filtering.ignore_cache_events` / `ignore_queries` / `ignore_outgoing_requests` exists in the host config it wins, and its sense is inverted (`true` = disabled). The package no longer ships those keys, so they can only come from the host's own config.

**Why:** Allows granular control over which sensors run; the legacy branch keeps older published host configs working, and the queue exception keeps trace propagation alive when only one queue sensor is on.

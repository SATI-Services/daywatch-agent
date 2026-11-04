---
title: Queue plumbing injects a daywatch.job_id Uuid v4 into queued job payloads for trace propagation
tags: [queue, sensors, trace]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
`Queue::createPayloadUsing()` adds `['daywatch' => ['job_id' => Uuid::v4()]]` to queued job payloads, read back as `payload['daywatch']['job_id']`. The id travels with the payload across queue hops, so it propagates the dispatch trace.

Only registered when `Queue` exists and at least one of `QueuedJobSensor` / `JobAttemptSensor` is enabled (`daywatch.sensors.{class}.enabled`, default true). The callback catches throwables and returns `[]`, so a given payload may carry no `job_id`.

**Why:** Connects queue executions to the same trace as their dispatch event.

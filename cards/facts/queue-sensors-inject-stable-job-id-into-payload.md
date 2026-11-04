---
title: Queue.createPayloadUsing injects a Uuid v4 job_id at daywatch.job_id into queued payloads
tags: [queue, sensors, trace]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
`attachQueueSensors()` registers `Queue::createPayloadUsing()` to add `['daywatch' => ['job_id' => Uuid::v4()]]` to queued job payloads, giving each job a stable id for trace propagation across queue hops. The hook is only registered when `QueuedJobSensor` or `JobAttemptSensor` is enabled, and if the callback throws it returns `[]` — no id is injected.

**Why:** Connects queue executions to the same trace as their dispatch event.

---
title: QueuedJobSensor queueing() and queued() catch all Throwable, never thrown
tags: [sensor, queued-job, queue, robustness]
status: verified 2026-11-10 auto
source: [src/Sensors/QueuedJobSensor.php]
as_of: 273b424ce 2026-11-10
---
QueuedJobSensor.queueing() and queued() wrap all work in try/catch blocks that catch Throwable — telemetry recording failure can never break a job dispatch.

**Why:** Cardinal rule — telemetry loss is acceptable; a dispatch must never fail loudly.

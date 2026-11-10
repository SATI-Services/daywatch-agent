---
title: QueuedJobSensor stashes queueingAt microtime so queued() can compute enqueue duration
tags: [sensor, queued-job, queue]
status: verified 2026-11-10 auto
source: [src/Sensors/QueuedJobSensor.php]
as_of: 273b424ce 2026-11-10
---
QueuedJobSensor.queueing() stashes the start microtime; queued() uses it to compute enqueue duration in microseconds. Unpaired queueing → duration is 0.

**Why:** JobQueueing and JobQueued events are separate; the sensor must bridge them to measure the enqueue window.

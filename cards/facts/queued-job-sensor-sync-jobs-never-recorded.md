---
title: QueuedJobSensor skips sync connection jobs
tags: [sensor, queued-job, queue]
status: verified 2026-11-10 auto
source: [src/Sensors/QueuedJobSensor.php]
as_of: 273b424ce 2026-11-10
---
`QueuedJobSensor::queued()` returns early when the job's `connectionName` is `sync`, so a sync job is never emitted as a separate `queued-job` record (it also clears the stashed `queueingAt`).

**Why:** Sync jobs run inline; there is nothing queued to observe and no queue delay to measure.

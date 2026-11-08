---
title: JobAttemptSensor captures attempt metadata on JobProcessing; skips sync and ignored jobs
tags: [sensor, queue, lifecycle]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: c34aba0b3 2026-11-08
---
`JobAttemptSensor::processing()` is the JobProcessing handler: it resets the attempt's execution state, captures `name`, `connection`, `queue`, `job_id` and `attempt` into `$meta`, then sets `active = true` and calls `core->prepareForJob()`.

Two cases skip it entirely — `active` is set false and nothing is captured: `sync`-connection jobs, and jobs whose name matches the sensor's `ignore` option (`daywatch.sensors`, shared with QueuedJobSensor).

The sensor is only wired in worker processes.

**Why:** JobProcessing runs before the job executes, so the captured metadata is fresh at that point.

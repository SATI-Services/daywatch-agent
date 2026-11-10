---
title: QueuedJobSensor resolves job name from string, displayName(), or class
tags: [sensor, queued-job, queue]
status: verified 2026-11-10 auto
source: [src/Sensors/QueuedJobSensor.php]
as_of: 273b424ce 2026-11-10
---
`QueuedJobSensor::name()` returns: the job itself when `$event->job` is a string; otherwise, for an object, `displayName()` when the method exists and returns a non-empty string; otherwise `get_class($job)`. Returns `''` when the job is neither a string nor an object, or when anything throws.

**Why:** Different queue drivers provide the job name in different forms; the sensor normalizes them.

---
title: QueuedJobSensor extracts daywatch job_id from event payload array
tags: [sensor, queued-job, queue, propagation]
status: verified 2026-11-10 auto
source: [src/Sensors/QueuedJobSensor.php]
as_of: 273b424ce 2026-11-10
---
QueuedJobSensor.jobId() looks for $payload['daywatch']['job_id'] if the event has a payload() method. Returns empty string if not found or on any error.

**Why:** Trace propagation injects a job id into the queue payload so queued and attempted jobs can be linked.

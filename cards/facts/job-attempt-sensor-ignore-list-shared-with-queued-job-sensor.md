---
title: JobAttemptSensor's ignore option (daywatch.sensors) is shared with QueuedJobSensor
tags: [sensor, queue, config]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: c34aba0b3 2026-11-08
---
JobAttemptSensor's `ignore` option (`daywatch.sensors`, see config/daywatch.php) is shared with QueuedJobSensor. Jobs whose name matches the ignore pattern are never recorded.

**Why:** No execution is started for an ignored job, so the attempt — and anything it does — is invisible to the system.

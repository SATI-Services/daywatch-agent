---
title: JobAttemptSensor captures job metadata on JobProcessing, skipping sync and ignored jobs
tags: [sensor, queue, lifecycle]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: be428b1b8 2026-11-08
---
`JobAttemptSensor::processing()` is the JobProcessing handler: it resets the attempt's execution state, captures `name`, `connection`, `queue`, `job_id` and `attempt` into `$meta`, sets `active = true` and calls `$core->prepareForJob()`. It writes nothing — the `job-attempt` record is written later, from `processed`/`released`/`failed`.

Two cases skip it and leave `active` false: `sync`-connection jobs, and job names matching the sensor's `ignore` option (`daywatch.sensors`, shared with QueuedJobSensor).

**Why:** `sync` jobs run inline, so their parent execution already captures them; an `ignore`-matched job starts no execution at all, so its attempt — and anything it does — is invisible.

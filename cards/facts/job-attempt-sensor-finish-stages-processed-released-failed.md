---
title: JobAttemptSensor writes one job-attempt record per processed, released or failed finish
tags: [sensor, queue, lifecycle]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: 6d42c7c32 2026-11-08
---
JobAttemptSensor's `processed`, `released` and `failed` handlers each call `finish($status)`, which writes one `job-attempt` record carrying that status — but only while `active` is true.

At JobProcessing the sensor leaves `active = false` in three distinct cases:

- Connection is `sync` — the job runs inline, so its work is captured by the parent execution; the sync job itself is not recorded as an attempt.
- The job name matches the sensor's `ignore` option (`daywatch.sensors` in config/daywatch.php, the list shared with QueuedJobSensor) — no execution is started for it, so the attempt and anything it does are invisible.
- Any `Throwable` is raised while capturing metadata — the attempt is not recorded and no execution is started.

**Why:** The skip reasons are not interchangeable: a sync job stays visible under its parent execution, an ignored job is recorded nowhere, and a thrown metadata capture drops the attempt silently.

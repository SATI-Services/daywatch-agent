---
title: ScheduledTaskSensor writes one record per task finish, only while active
tags: [sensors, execution-stages]
status: verified 2026-11-10 auto
source: [src/Sensors/ScheduledTaskSensor.php]
as_of: 72d7040e7 2026-11-09
---
ScheduledTaskSensor writes one `scheduled-task` record per task finish, with status `processed` (from `finished`), `skipped` (from `skipped`) or `failed` (from `failed`).

**Why:** Each outcome is a distinct execution that must be observed and counted separately.

**Unless active, nothing is written:** `finish()` returns early when the sensor is not active — i.e. when `starting` never ran (or threw) for that run, or the flag was already consumed. The sensor is only wired in the scheduler process.

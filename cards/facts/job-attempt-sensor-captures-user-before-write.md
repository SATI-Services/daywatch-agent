---
title: JobAttemptSensor calls UserSensor.capture() immediately before writing the attempt record
tags: [sensor, queue, user]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: c34aba0b3 2026-11-08
---
In `finish()`, JobAttemptSensor calls `$this->users->capture()` (a `UserSensor`) on the line immediately before `$core->write($record->toArray())`.

This happens only when the sensor is active — `finish()` returns early otherwise, so `sync` jobs and job names matching the sensor's `ignore` option are skipped — and only for the `processed`, `released` and `failed` statuses.

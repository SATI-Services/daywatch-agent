---
title: NotificationSensor duration computed, rounded, and clamped to ≥ 0 microseconds
tags: [sensors, notification, timing]
status: verified 2026-11-10 auto
source: [src/Sensors/NotificationSensor.php]
as_of: ebeca95d9 2026-11-10
---
NotificationSensor computes duration as `(int) round(max(0.0, now - start) * 1_000_000)` in integer microseconds, where `start` is the Sending timestamp stashed by `spl_object_id`; an unpaired Sent falls back to `start = now` and yields 0.

**Why:** It feeds the `notification` record's send timing; the whole path is try/caught so telemetry loss never surfaces into the host app.

---
title: ScheduledTaskRecord duration is rounded microseconds since requestStartedAt, 0 if start is invalid
tags: [records, timing]
status: verified 2026-11-10 auto
source: [src/Sensors/ScheduledTaskSensor.php]
as_of: 72d7040e7 2026-11-09
---
`ScheduledTaskSensor::duration()` returns `(int) round((clock()->microtime() - requestStartedAt()) * 1_000_000)` — microseconds — clamped with `max(0, …)` so the value is never negative.

If `requestStartedAt()` is `<= 0.0` it returns `0` without reading the clock.

---
title: ScheduledTaskSensor captures task metadata on ScheduledTaskStarting event
tags: [sensors, lifecycle]
status: verified 2026-11-10 auto
source: [src/Sensors/ScheduledTaskSensor.php]
as_of: 72d7040e7 2026-11-09
---
ScheduledTaskSensor captures the task's name, cron (`$task->expression`), timezone, and three boolean flags — `withoutOverlapping`, `onOneServer`, `runInBackground` — into `$meta` on the ScheduledTaskStarting event.

**Why:** `finish()` reads `$meta` back to build the `ScheduledTaskRecord`, so the definition must be captured at start.

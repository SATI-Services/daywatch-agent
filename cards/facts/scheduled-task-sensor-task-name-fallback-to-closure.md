---
title: ScheduledTaskSensor names a task getSummaryForDisplay, then command, then description, else 'Closure'
tags: [sensors, naming]
status: verified 2026-11-10 auto
source: [src/Sensors/ScheduledTaskSensor.php]
as_of: 72d7040e7 2026-11-09
---
`ScheduledTaskSensor::taskName()` takes the first non-empty of: the method `getSummaryForDisplay()` (only when the task object has that method), the `command` property, then the `description` property. If the task is not an object, or all three are empty, it returns `Closure`.

**Why:** `taskName()` is wrapped in try/catch and always returns a string, so a task object exposing no name still yields a named `scheduled-task` record instead of throwing.

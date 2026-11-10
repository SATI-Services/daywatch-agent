---
title: ScheduledTaskSensor only wired in the scheduler process
tags: [sensors, lifecycle]
status: verified 2026-11-10 auto
source: [src/Sensors/ScheduledTaskSensor.php]
as_of: 72d7040e7 2026-11-09
---
`ScheduledTaskSensor` is only wired in the scheduler process; the class docblock states this.

**Why:** It captures one run of a scheduled task into a `scheduled-task` record, each task run its own execution under a fresh trace.

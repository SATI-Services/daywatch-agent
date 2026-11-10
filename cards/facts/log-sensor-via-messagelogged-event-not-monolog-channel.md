---
title: LogSensor hooks MessageLogged event, not a custom Monolog channel
tags: [sensors, logging]
status: verified 2026-11-10 auto
source: [src/Sensors/LogSensor.php]
as_of: 72d7040e7 2026-11-09
---
LogSensor attaches to Illuminate\Log\Events\MessageLogged, not a custom Monolog channel.

**Why:** Framework events are simpler, more portable, and yield identical log records.

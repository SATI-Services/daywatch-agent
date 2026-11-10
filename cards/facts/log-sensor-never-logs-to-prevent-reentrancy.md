---
title: LogSensor never logs to prevent reentrancy loop
tags: [sensors, safety]
status: verified 2026-11-10 auto
source: [src/Sensors/LogSensor.php]
as_of: 72d7040e7 2026-11-09
---
LogSensor never logs — logging would re-enter the sensor and create a loop.

**Why:** Pure handler protects against infinite recursion.

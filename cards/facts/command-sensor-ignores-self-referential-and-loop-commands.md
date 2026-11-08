---
title: CommandSensor ignores 10 self-referential and loop commands
tags: [sensor, command, observability]
status: verified 2026-11-08 auto
source: [src/Sensors/CommandSensor.php]
as_of: 2cf41c87a 2026-11-08
---
CommandSensor never records commands: daywatch:agent, daywatch:status, schedule:run, schedule:work, queue:work, queue:listen, horizon, horizon:work, horizon:supervisor, package:discover.

**Why:** These commands are self-observation, long-running loops, or framework noise that would distort metrics and create unbounded observation.

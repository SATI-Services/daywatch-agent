---
title: CommandSensor walks three command execution stages
tags: [sensor, command, lifecycle]
status: verified 2026-11-08 auto
source: [src/Sensors/CommandSensor.php]
as_of: 2cf41c87a 2026-11-08
---
`CommandSensor` walks three command stages: bootstrap, closed in `starting()` on `CommandStarting` via `stages->advance(ExecutionStage::BOOTSTRAP)`; action, opened by `beginStage(ExecutionStage::ACTION)` at the top of `finished()`; terminating, opened by `beginStage(ExecutionStage::TERMINATING)` after the `CommandRecord` is built, before `users->capture()` and `core->write()`. Each boundary call records a stage; the record carries them via `core->stages()`.

**Why:** Per the class docblock, the command is its own execution root and walks the three command stages (bootstrap → action → terminating).

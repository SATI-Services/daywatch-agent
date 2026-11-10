---
title: ScheduledTaskSensor catches Throwable in starting() and finish(), not its constructor
tags: [safety, sensors]
status: verified 2026-11-10 auto
source: [src/Sensors/ScheduledTaskSensor.php]
as_of: a8974c9d6 2026-11-10
---
In `ScheduledTaskSensor`, `starting()` and the private `finish()` wrap their bodies in `catch (Throwable)`. `finished()`, `skipped()` and `failed()` are thin wrappers that all call `finish()`, so they inherit that guard. `__construct()` has no guard. The helper reads `taskName()`, `stringProp()` and `timezone()` each catch `Throwable` internally and return a fallback (`'Closure'` or `''`).

**Why:** On a throw in `starting()` the catch sets `$this->active = false` so no record is written; `finish()` returns early when inactive and otherwise swallows, so a failed record write cannot propagate. `$this->users = $users ?? new UserSensor($core)` in the constructor is the one path with no try/catch.

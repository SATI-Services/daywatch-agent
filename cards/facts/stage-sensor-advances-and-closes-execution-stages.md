---
title: StageSensor.advance() closes a stage and advances to the next in the execution state machine
tags: [sensor, stages, execution]
status: verified 2026-11-10 auto
source: [src/Sensors/StageSensor.php]
as_of: 273b424ce 2026-11-10
---
`StageSensor::advance(string $closingStage)` calls `$this->core->beginStage($closingStage)` to close the given stage (accumulating its microseconds into `Core`) and advance to its successor. The per-stage microseconds land as columns on the request/command record; stage timing is not itself a record type.

Every transition is guarded: the call is wrapped in `try { … } catch (Throwable) {}`, so a stage-timing failure can never disturb the host request.

**Why:** The execution-stage state machine walks requests through bootstrap → before_middleware → action → render → after_middleware → sending → terminating → end, and commands through bootstrap → action → terminating → end.

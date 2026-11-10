---
title: StageSensor.advance() wrapped in try/catch; stage timing failures never disturb the request
tags: [sensor, stages, execution, robustness]
status: verified 2026-11-10 auto
source: [src/Sensors/StageSensor.php]
as_of: 273b424ce 2026-11-10
---
StageSensor.advance() wraps the core.beginStage() call in try/catch(Throwable) — a stage-timing failure is silently swallowed and never surfaces to the host request.

**Why:** Cardinal rule — a stage-timing failure can never disturb the host request.

---
title: JobAttemptSensor.finish() writes the job-attempt record for active attempts
tags: [sensor, queue, record]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: be428b1b8 2026-11-08
---
`JobAttemptSensor::finish($status)` is the single writer behind `processed`, `released` and `failed`: it builds a `JobAttemptRecord` and calls `$core->write($record->toArray())`, then `$core->finishExecution()`, and it returns without writing unless the attempt is `active`.

Only `context` is the empty object `'{}'`. `counters` come from `$core->counters()`, `peakMemoryUsage` from `memory_get_peak_usage(true)`, `exceptionPreview` from `$core->exceptionPreview()`, and `attemptId` from `$core->executionId`.

**Why:** Nothing is written unless the attempt is active, so a `sync` job (run inline, captured by its parent execution) or an `ignore`-matched job leaves no `job-attempt` record at all.

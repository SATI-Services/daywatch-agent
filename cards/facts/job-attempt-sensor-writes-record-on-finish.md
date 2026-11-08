---
title: JobAttemptSensor.finish() writes a JobAttemptRecord for active attempts
tags: [sensor, queue, record]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: c34aba0b3 2026-11-08
---
`JobAttemptSensor::finish()` builds a `JobAttemptRecord` with `counters`, `peakMemoryUsage`, `exceptionPreview` and `context` set to the empty object `'{}'`, and writes it through `$core->write($record->toArray())`.

Nothing is written unless the attempt is active: `processing()` sets `active = false` for `sync`-connection jobs (they run inline, captured by their parent execution) and for job names matching the sensor's `ignore` option (shared with `QueuedJobSensor`).

**Why:** A sync or ignored job leaves no `job-attempt` record at all — the attempt, and anything it does, is invisible.

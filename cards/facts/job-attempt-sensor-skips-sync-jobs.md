---
title: JobAttemptSensor skips sync jobs; captured by parent execution
tags: [sensor, queue, job]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: c34aba0b3 2026-11-08
---
JobAttemptSensor skips sync jobs (connection === 'sync'). Sync jobs run inline and are captured by their parent execution.

**Why:** Sync jobs have no worker lifecycle and no separate execution boundary.

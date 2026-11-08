---
title: JobAttemptSensor only wired in worker processes
tags: [sensor, queue, worker]
status: verified 2026-11-08 auto
source: [src/Sensors/JobAttemptSensor.php]
as_of: c34aba0b3 2026-11-08
---
JobAttemptSensor is only wired in worker processes, not in web requests.

**Why:** Job attempts execute only in worker/queue runners.

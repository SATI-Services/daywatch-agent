---
title: The queued-job sensor skips `sync` connection jobs
tags: [sensors, queue]
status: verified 2026-11-09 auto
source: [src/Records/QueuedJobRecord.php]
as_of: 20af138a0 2026-11-09
---
The `queued-job` record (agent protocol §2) is written for a job being dispatched onto a queue (`JobQueueing`→`JobQueued`); `sync` connection jobs are skipped by the sensor, so they produce no such record.

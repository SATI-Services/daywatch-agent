---
title: 14 sensors buffer to bounded limits: ≤500 records AND ≤buffer_bytes
tags: [sensors, buffer, limits, architecture]
status: verified 2026-09-09 auto
source: [CLAUDE.md, README.md]
as_of: 16a6c506a 2026-09-06
---
The package includes 14 sensors (Request, Query, Exception, Cache, Command, JobAttempt, Log, Mail, Notification, OutgoingRequest, QueuedJob, ScheduledTask, Stage, User) that observe framework events into a bounded per-execution buffer with TWO constraints: ≤500 records AND ≤buffer_bytes. Buffer auto-digests when full; ring-drop if collection is disabled.

**Why:** Dual bounds prevent both count-based and memory-based overruns. A single cap on count alone would fail if one record carried megabytes; byte caps alone fail with many small records.

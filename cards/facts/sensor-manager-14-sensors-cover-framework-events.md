---
title: SensorManager wires 14 sensors covering Request, Query, Exception, Cache, Command, and more
tags: [sensors, architecture, events]
status: verified 2026-09-09 auto
source: [CLAUDE.md, README.md]
as_of: 16a6c506a 2026-09-06
---
`SensorManager` wires 14 sensors: Request, Query, Exception, Cache, Command, JobAttempt, Log, Mail, Notification, OutgoingRequest, QueuedJob, ScheduledTask, Stage, and User. Each sensor observes framework events and buffers records.

**Why:** Comprehensive sensor coverage gives a complete picture of application behavior across the request lifecycle and background jobs.

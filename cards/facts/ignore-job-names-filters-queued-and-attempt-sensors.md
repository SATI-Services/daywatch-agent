---
title: filtering.ignore_job_names (default empty) filters both QueuedJobSensor and JobAttemptSensor
tags: [filtering, job-sensor, config]
status: verified 2026-10-23 auto
source: [docs/sessions/ryan/2026-10-06-filtering-jobs-and-queries.md]
as_of: bd5f9eb69 2026-10-23
---
`filtering.ignore_job_names` (default empty, env `DAYWATCH_IGNORE_JOB_NAMES`) matched against job display name / class in both QueuedJobSensor (no queued-job record; stashed timestamp discarded) and JobAttemptSensor (no execution started — attempt and its work invisible).

**Why:** Added as the jobs analogue of `ignore_cache_keys`, one of the two extensions Ryan gave the go-ahead for on the M4 query-filtering work.

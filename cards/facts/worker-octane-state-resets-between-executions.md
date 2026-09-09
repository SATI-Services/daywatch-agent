---
title: Worker/Octane state resets between executions; worker-loop noise never sampled
tags: [worker, octane, state-reset, sampling]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
Worker and Octane state resets between executions: fresh trace/execution IDs, counters reset, `memory_reset_peak_usage()` called. Worker-loop noise is never sampled.

**Why:** Long-running workers would otherwise accumulate state across requests; resetting ensures each execution is independent and memory accounting is accurate.

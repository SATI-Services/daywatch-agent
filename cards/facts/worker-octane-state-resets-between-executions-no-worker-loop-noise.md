---
title: Worker/Octane state resets between executions; worker-loop noise is never sampled
tags: [worker, state, sampling]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
Worker/Octane state resets between executions (fresh ids, counters, `memory_reset_peak_usage()`); worker-loop noise is never sampled.

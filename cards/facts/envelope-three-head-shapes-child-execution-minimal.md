---
title: Envelopes declare three head shapes: child(t,group) for in-execution, execution(t,group) for executions, minimal(t) for user
tags: [records, wire-contract, envelope]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
All shared wire fields live in `Records/Envelope.php` — three head shapes: `child(t,group)` for records emitted *during* an execution, `execution(t,group)` for records that *are* one, `minimal(t)` for `user` plus `Counters::tail()` for the twelve counters + `peak_memory_usage`/`exception_preview`/`context` that every execution-root record ends with.

**Why:** Shared fields must be in one place. Three head shapes cover the three record types without duplication.

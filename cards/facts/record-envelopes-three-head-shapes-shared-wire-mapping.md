---
title: Envelopes declare three head shapes: child(t,group) for in-execution, execution(t,group) for executions, minimal(t) for user
tags: [envelope, records, wire-contract, protocol]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
`Records/Envelope.php` defines three head shapes: `child(t, group)` for records emitted *during* an execution, `execution(t, group)` for records that *are* one, and `minimal(t)` for user. All share `Counters::tail()` (twelve counters + peak_memory_usage, exception_preview, context) on execution-root records.

**Why:** Enveloping standardizes which fields every record type must carry, centralizing the wire contract in one place.

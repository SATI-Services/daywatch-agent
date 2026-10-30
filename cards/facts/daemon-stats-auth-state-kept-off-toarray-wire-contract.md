---
title: Auth state and auth failures kept off STATS toArray() — frozen wire contract
tags: [daemon, stats, wire-contract]
status: verified 2026-10-30 auto
source: [src/Daemon/DaemonStats.php]
as_of: a38bcc9ca 2026-10-30
---
`DaemonStats::toArray()` excludes `authState`, `authSummary` and `authFailures`. Only `base_url`, the record/batch/byte counters and the last-flush fields are in the payload — the STATS reply wire contract (agent protocol §4).

`authState()` / `authSummary()` are surfaced by the live `ConsoleDashboard` and the boot log; `authFailures()` (count of 401 rejections) by the `ConsoleDashboard` only, not the boot log.

**Why:** The exclusions are deliberate — that array is the frozen STATS wire contract, so diagnostic-only auth fields stay out of it.

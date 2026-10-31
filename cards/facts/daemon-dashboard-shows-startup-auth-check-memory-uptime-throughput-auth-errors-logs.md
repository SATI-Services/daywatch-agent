---
title: TTY dashboard shows startup auth check, memory/uptime, ingest throughput, auth errors (401s), and last N log lines
tags: [daemon, console, observability]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
The TTY dashboard displays: the startup auth check, memory/uptime, ingest throughput, auth errors (401s tracked via `DaemonStats::authFailed()`, kept off the frozen STATS `toArray()` contract), and the last N log lines. The daemon's logger feeds a bounded `Daemon/RecentLog` ring buffer in TTY mode.

**Why:** Operators running the daemon in a terminal need real-time visibility into health, throughput, and errors.

---
title: TTY dashboard shows auth check, memory/uptime, throughput, auth errors, recent log lines
tags: [daemon, tty, console, dashboard]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 58f3dad19 2026-09-09
---
The TTY dashboard displays: the startup auth check (the `auth` row), memory/uptime, ingest throughput, auth errors (401s tracked in-process via `DaemonStats::authFailed()`), and the last N log lines — in this mode the daemon's logger feeds a bounded `Daemon/RecentLog` ring buffer that the dashboard reads from.

**Why:** Everything the dashboard shows is read from in-process daemon state (`DaemonStats`, `RecentLog`), so operators see auth failures and throughput live without any change to what the daemon reports externally.

---
title: Stats surfaces via one-line stdout log every DAYWATCH_DAEMON_STATS_INTERVAL s (default 60, 0 disables) and daywatch:status
tags: [daemon, stats, observability]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
Stats surfaces: a one-line stats log on the daemon's stdout every `daywatch.daemon.stats_interval` s (env `DAYWATCH_DAEMON_STATS_INTERVAL`, default 60, `0` disables; `Daemon/StatsReporter`, guarded so a logging failure can never crash the loop), and `daywatch:status` (STATS frame → ack + `{len}:{json}` reply, token-gated).

**Why:** Operators need to see daemon health without instrumenting the ingest. Guarding prevents a logging failure from crashing the daemon.

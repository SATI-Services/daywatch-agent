---
title: STATS surfaces via periodic stdout log and daywatch:status command
tags: [daemon, observability, stats, cli]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
`STATS` counters surface via: (1) a one-line log on daemon stdout every `daywatch.daemon.stats_interval` s (env `DAYWATCH_DAEMON_STATS_INTERVAL`, default 60, `0` disables; `Daemon/StatsReporter`, guarded so logging failure never crashes), and (2) `daywatch:status` (STATS frame → ack + `{len}:{json}` reply, token-gated; human table or `--json`; daemon down = clear "daemon unreachable" message + exit 1).

**Why:** Operators see steady heartbeat on stdout; `daywatch:status` is the CLI query surface for monitoring and script integration.

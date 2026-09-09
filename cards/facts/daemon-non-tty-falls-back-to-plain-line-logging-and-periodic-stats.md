---
title: Daemon falls back to plain-line logging + periodic stats on non-TTY, --plain, or refresh 0
tags: [daemon, tty, console, logging]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: dbb7df637 2026-09-09
---
When run with `--plain`, attached to a non-TTY (for example under Supervisor or logging to a file), or when `daywatch.daemon.console_refresh` is `0`, the daemon falls back to plain-line logging and emits the periodic one-line STATS log instead of the in-place TTY dashboard. The periodic STATS line is printed every `daywatch.daemon.stats_interval` seconds (env `DAYWATCH_DAEMON_STATS_INTERVAL`, default 60; `0` disables) and provides the O(1) counters (records received/buffered, buffered bytes, batches sent, send failures, retries, last flush time/size, and configured base URL).

**Why:** Plain-line logging preserves log-file/supervisor integration and still surfaces daemon liveness via the periodic STATS line.

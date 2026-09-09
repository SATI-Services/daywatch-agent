---
title: DAYWATCH_DAEMON_CONSOLE_REFRESH and DAYWATCH_DAEMON_CONSOLE_LINES tune the TTY dashboard
tags: [daemon, tty, console, dashboard, config]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 58f3dad19 2026-09-09
---
`daywatch.daemon.console_refresh` (env `DAYWATCH_DAEMON_CONSOLE_REFRESH`, default `3` seconds) sets the dashboard repaint interval; `0` disables the dashboard and falls back to plain-line logging. `daywatch.daemon.console_lines` (env `DAYWATCH_DAEMON_CONSOLE_LINES`, default `10`) sets how many recent log lines the dashboard keeps.

**Why:** Operators can slow or disable repaints and size the visible log tail; refresh `0` is one of the three triggers (with non-TTY and `--plain`) for the plain-line fallback.

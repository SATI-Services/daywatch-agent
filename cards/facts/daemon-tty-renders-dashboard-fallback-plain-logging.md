---
title: TTY daemon renders live in-place dashboard; non-TTY, --plain, or refresh 0 falls back to plain-line logging + periodic stats
tags: [daemon, console, observability]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
When `daywatch:agent` runs attached to a **TTY** it renders a live in-place dashboard (`Daemon/ConsoleDashboard`, self-rescheduling + guarded). Repaints every `daywatch.daemon.console_refresh` s (env `DAYWATCH_DAEMON_CONSOLE_REFRESH`, default 3, `0` disables), keeping `daywatch.daemon.console_lines` (`DAYWATCH_DAEMON_CONSOLE_LINES`, default 10) lines.

`--plain`, a non-TTY (supervisor/log file), or refresh 0 falls back to plain-line logging + the periodic stats line.

**Why:** A TTY-attached daemon can render a rich dashboard for human operators; a non-TTY daemon falls back to plain lines for log collectors and supervisors.

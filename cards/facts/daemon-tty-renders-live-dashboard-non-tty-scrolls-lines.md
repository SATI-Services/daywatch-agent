---
title: Daemon renders a live in-place dashboard via ConsoleDashboard when attached to a TTY
tags: [daemon, tty, console, dashboard]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 58f3dad19 2026-09-09
---
When `php artisan daywatch:agent` runs attached to a TTY it renders a live in-place dashboard (`Daemon/ConsoleDashboard`) instead of scrolling log lines. The dashboard is self-rescheduling and guarded like `StatsReporter`, so a render failure can never crash the daemon loop. A non-TTY, `--plain`, or a console refresh of `0` falls back to plain-line logging plus the periodic stats line.

**Why:** Gives operators live visibility during startup and troubleshooting; the guard keeps display-only cosmetics from ever taking the daemon down.

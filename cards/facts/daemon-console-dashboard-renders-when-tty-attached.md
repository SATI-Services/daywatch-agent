---
title: TTY-attached daemon renders live in-place dashboard; non-TTY falls back to plain logging
tags: [daemon, ui, operations]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: 80c896873 2026-09-09
---
When `daywatch:agent` is attached to a TTY, `Daemon/ConsoleDashboard` renders a live in-place dashboard showing auth check, memory/uptime, throughput, auth errors, and recent log lines. Non-TTY (supervisor/log file), `--plain`, or refresh `0` falls back to plain-line logging + periodic stats line.

**Why:** Operators monitoring the daemon directly need visibility; supervised/background runs need scrolling logs and stats intervals instead.

---
title: ConsoleDashboard repaints are self-rescheduling and guarded so rendering failures cannot crash the loop
tags: [daemon, console, resilience]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: c8cd1c517 2026-09-10
---
When attached to a TTY the daemon uses Daemon/ConsoleDashboard to render a live in-place dashboard; the dashboard is self-rescheduling and guarded (exceptions during repaint are caught) so a rendering failure cannot crash the event loop.

**Why:** The dashboard must not be a single point of failure; guarding repaints preserves the daemon's un-crashable requirement, mirroring the same guarded approach used by the periodic StatsReporter for one-line stats logging.

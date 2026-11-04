---
title: StatsReporter emits daemon stats line periodically; emit and reschedule both guarded
tags: [daemon, stats, logging]
status: verified 2026-11-04 auto
source: [src/Daemon/StatsReporter.php]
as_of: 23d988245 2026-11-04
---
StatsReporter emits one stats log line every `daemon.stats_interval` seconds (agent protocol §5), self-rescheduling through the injected `Scheduler` (the ReactPHP loop in prod, a fake in tests). `start()` is a no-op when the interval is 0 or negative, which disables the line entirely. `emit()` is guarded — any logging failure is caught and the line stops, but the loop is never crashed. Rescheduling is also guarded; worst case the periodic line simply stops.

**Why:** An operator watching supervisor stdout sees the daemon is alive and moving records. Both the emit and the reschedule must never throw into the event loop.

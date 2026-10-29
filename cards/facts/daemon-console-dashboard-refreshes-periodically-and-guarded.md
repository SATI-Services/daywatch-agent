---
title: ConsoleDashboard repaints guarded; rendering failures never crash the loop
tags: [daemon, console, resilience]
status: verified 2026-10-29 auto
source: [src/Daemon/ConsoleDashboard.php]
as_of: 541096adf 2026-10-29
---
ConsoleDashboard `render()` and `scheduleNext()` are fully guarded — every `Throwable` is caught, so a rendering or writer failure cannot crash the event loop. Worst case the display freezes; the daemon keeps ingesting and forwarding.

**Why:** Live dashboard output failures must never block or stop the ingest loop.

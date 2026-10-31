---
title: StatsReporter guarded so a logging failure can never crash the loop
tags: [daemon, resilience, stats]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
`Daemon/StatsReporter` is guarded so a logging failure can never crash the event loop.

**Why:** An exception during stats reporting must not crash the daemon or stop record ingestion.

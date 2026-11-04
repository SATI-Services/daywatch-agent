---
title: StatsReporter.start() disables when interval is ≤ 0
tags: [daemon, stats]
status: verified 2026-11-04 auto
source: [src/Daemon/StatsReporter.php]
as_of: 23d988245 2026-11-04
---
StatsReporter.start() does nothing (no-op) when intervalSeconds is 0 or negative, leaving the periodic line armed off.

**Why:** Allows the interval setting to disable the feature.

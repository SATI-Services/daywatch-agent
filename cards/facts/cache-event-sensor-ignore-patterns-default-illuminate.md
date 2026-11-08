---
title: CacheEventSensor ignores `*illuminate:*` keys by default
tags: [cache, sensors, config]
status: verified 2026-11-08 auto
source: [src/Sensors/CacheEventSensor.php]
as_of: 073ae2e05 2026-11-07
---
CacheEventSensor's default `ignore` pattern is `*illuminate:*` — the framework's own internal keys such as `illuminate:queue:restart`. The ignore patterns are config-driven under `daywatch.sensors` (see `config/daywatch.php`) and are applied to the raw key before anything is buffered.

**Why:** Framework cache keys are high-volume and not actionable; the default reduces noise.

---
title: CacheEventSensor groups config rewrites recorded key at emit time
tags: [cache, sensors, config, grouping]
status: verified 2026-11-08 auto
source: [src/Sensors/CacheEventSensor.php]
as_of: 073ae2e05 2026-11-07
---
CacheEventSensor `groups` config (Pulse-style regex ⇒ replacement, first match wins) rewrites the recorded key to a group label at emit time. Hash-suffixed keys collapse into one `_group`.

**Why:** The group is computed from the RAW key, so start/completion duration pairing is never disturbed.

---

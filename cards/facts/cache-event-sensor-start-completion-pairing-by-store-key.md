---
title: CacheEventSensor pairs start/completion events by store|key to compute duration
tags: [cache, sensors]
status: verified 2026-11-08 auto
source: [src/Sensors/CacheEventSensor.php]
as_of: 073ae2e05 2026-11-07
---
CacheEventSensor pairs start events (Retrieving/Writing/Forgetting) with completion events (hit/miss/write/delete and their failures) via the `store|key` pending key to compute a `duration` in integer microseconds. Unpaired completions record duration 0.

**Why:** Duration requires timing both endpoints; an unmatched completion has no start left to measure against — the pending map is in memory only and is cleared wholesale when it reaches `MAX_PENDING` (1000).

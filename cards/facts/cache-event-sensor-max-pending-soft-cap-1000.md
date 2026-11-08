---
title: CacheEventSensor MAX_PENDING soft cap is 1000 to prevent unbounded growth
tags: [cache, sensors, safety]
status: verified 2026-11-08 auto
source: [src/Sensors/CacheEventSensor.php]
as_of: 073ae2e05 2026-11-07
---
CacheEventSensor holds unmatched start events in memory with MAX_PENDING = 1000. When capacity is reached, the buffer is cleared to prevent unbounded growth from unbalanced start/completion streams.

**Why:** Unbalanced streams (restart without flush, or start event lost) can accumulate indefinitely without a cap.

---

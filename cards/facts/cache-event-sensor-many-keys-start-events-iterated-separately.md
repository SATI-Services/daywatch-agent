---
title: CacheEventSensor iterates many-key start events per key to not disturb pairing
tags: [cache, sensors]
status: verified 2026-11-08 auto
source: [src/Sensors/CacheEventSensor.php]
as_of: 073ae2e05 2026-11-07
---
CacheEventSensor handles WritingManyKeys and RetrievingManyKeys by iterating ->keys array and recording each key separately. Start/completion pairing keyed by store|key is never disturbed.

**Why:** Many-key operations must pair individually; preserving raw key prevents pairing breakdown.

---

---
title: CacheEventSensor ttl field populated only on write events
tags: [cache, sensors]
status: verified 2026-11-08 auto
source: [src/Sensors/CacheEventSensor.php]
as_of: 073ae2e05 2026-11-07
---
CacheEventSensor ttl (in seconds) is populated only on write and write-failure events. All other types record ttl 0.

**Why:** Only writes have a TTL; read/delete events do not.

---

---
title: CacheEventSensor guards all property reads because event shapes vary across Laravel 11/12/13
tags: [cache, sensors, compat]
status: verified 2026-11-08 auto
source: [src/Sensors/CacheEventSensor.php]
as_of: 073ae2e05 2026-11-07
---
CacheEventSensor guards every event property read with a fallback — `$event->storeName`, `$event->key`, `$event->keys`, `$event->seconds` — because Illuminate cache event shapes vary across Laravel 11/12/13.

**Why:** Framework evolution changes event payloads; defensive reads prevent crashes.

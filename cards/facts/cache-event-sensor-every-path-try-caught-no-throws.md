---
title: CacheEventSensor every path try/caught to never throw into host app
tags: [cache, sensors, safety]
status: verified 2026-11-08 auto
source: [src/Sensors/CacheEventSensor.php]
as_of: 073ae2e05 2026-11-07
---
`CacheEventSensor::handle()` wraps its whole body in `try { … } catch (Throwable) {}`. Every path swallows the throwable; telemetry loss is acceptable, throwing into the host app is not.

**Why:** The class docblock: every property read is guarded because event shapes vary across Laravel 11/12/13, and every path is try/caught — telemetry loss is fine, throwing into the host app is not.

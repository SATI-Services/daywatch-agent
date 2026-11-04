---
title: Sensor singletons are resolved once at attach time, not per event
tags: [sensors, performance]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
Sensor instances are resolved from the container once during attach, then reused for all events. No per-event container lookups.

**Why:** Keeps the hot event path free of container overhead.

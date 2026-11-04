---
title: RequestSensor.finish() called via whenRequestLifecycleIsLongerThan or RequestHandled fallback
tags: [request-sensor, lifecycle]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
If the kernel has `whenRequestLifecycleIsLongerThan()`, SensorManager uses it with a -1ms threshold (always fires at request termination). Otherwise falls back to the RequestHandled event.

**Why:** Ensures RequestSensor.finish() runs once per request.

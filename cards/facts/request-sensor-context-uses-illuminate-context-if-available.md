---
title: RequestSensor.context() returns Context::all() as JSON or empty object
tags: [sensors, request, context]
status: verified 2026-11-08 auto
source: [src/Sensors/RequestSensor.php]
as_of: 16b149588 2026-11-08
---
RequestSensor.context() returns JSON-encoded Context::all() when Context class exists; returns '{}' on missing class or any exception.

**Why:** Captures Laravel's request context; graceful fallback for older Laravel versions.

---
title: CaptureRequest middleware guards RequestSensor.start(); never blocks request flow
tags: [middleware, request, sensor]
status: verified 2026-11-04 auto
source: [src/Http/Middleware/CaptureRequest.php]
as_of: 90ae942b6 2026-11-04
---
CaptureRequest middleware catches all exceptions from RequestSensor.start() and silently continues. Request always proceeds to next handler regardless of sensor outcome.

**Why:** Middleware is transparent; exceptions in sensor setup must never block the request or expose to the host app.

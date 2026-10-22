---
title: Service provider prependMiddleware(CaptureRequest::class) unless RequestSensor is disabled
tags: [middleware, boot, service-provider]
status: verified 2026-10-22 auto
source: [docs/sessions/ryan/2026-10-12-entry-point-and-facade-guard-tests.md]
as_of: 2fb153d96 2026-10-21
---
`SensorManager.attachRequestSensor()` prepends `CaptureRequest::class` to the application middleware stack at boot. When the RequestSensor is disabled, nothing is prepended. The Foundation kernel dedupes prepends; the middleware must be prepended or no request execution ever starts.

**Why:** The request sensor's entry point must run before other middleware to capture the bootstrap timing.

---
title: Http/Middleware/CaptureRequest starts request execution; exceptions never block the request
tags: [middleware, entry-point, uncrashable]
status: verified 2026-10-22 auto
source: [docs/sessions/ryan/2026-10-12-entry-point-and-facade-guard-tests.md]
as_of: 2fb153d96 2026-10-21
---
`Http/Middleware/CaptureRequest` is the request sensor's entry point: it starts the request execution (source `request`, bootstrap stage closed, stage µs ≥ 0) and passes the request untouched to the next middleware. Any exception escaping the sensor is caught; `$next` still runs and the response is returned. The cardinal rule at the HTTP boundary: telemetry never blocks the request.

**Why:** The package is uncrashable; a sensor exception cannot reach the host app.

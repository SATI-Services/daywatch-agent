---
title: OutgoingRequestSensor.resolveMessageSize() prefers the body stream size, then Content-Length, else 0
tags: [sensor, outgoing-request, http]
status: verified 2026-11-10 auto
source: [src/Sensors/OutgoingRequestSensor.php]
as_of: 273b424ce 2026-11-10
---
`OutgoingRequestSensor::resolveMessageSize()` (private) returns the body stream's `getSize()` when it is non-null; otherwise the `Content-Length` header value when the header is non-empty and numeric; otherwise 0. It catches `Throwable` and never throws.

**Why:** The source docblock states the order — prefer the stream size, fall back to `Content-Length`, else 0, never throws — and the `Throwable` catch makes sizing telemetry non-throwing.

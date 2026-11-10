---
title: OutgoingRequestSensor record() and guzzleMiddleware() swallow all Throwable
tags: [sensor, outgoing-request, http, robustness]
status: verified 2026-11-10 auto
source: [src/Sensors/OutgoingRequestSensor.php]
as_of: 273b424ce 2026-11-10
---
OutgoingRequestSensor.record() and guzzleMiddleware() catch Throwable at every step — recording failure can never break the response pipeline or surface to the host app.

**Why:** Telemetry loss is acceptable; an outgoing request must never fail loudly.

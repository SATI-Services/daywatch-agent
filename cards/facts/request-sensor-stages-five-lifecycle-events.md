---
title: RequestSensor marks five stage transitions via events
tags: [sensors, request, stages]
status: verified 2026-11-08 auto
source: [src/Sensors/RequestSensor.php]
as_of: 16b149588 2026-11-08
---
RequestSensor.start() closes BOOTSTRAP; routeMatched() closes BEFORE_MIDDLEWARE; preparingResponse() closes ACTION; responsePrepared() closes RENDER; requestHandled() closes AFTER_MIDDLEWARE. finish() opens SENDING then TERMINATING.

**Why:** Stage boundaries measure request execution shape.

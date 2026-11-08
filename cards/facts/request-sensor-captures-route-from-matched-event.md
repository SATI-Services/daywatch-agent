---
title: RequestSensor sets execution preview on RouteMatched event
tags: [sensors, request, preview]
status: verified 2026-11-08 auto
source: [src/Sensors/RequestSensor.php]
as_of: 16b149588 2026-11-08
---
RequestSensor.routeMatched() calls setExecutionPreview() with METHOD SPACE routePath from the route event.

**Why:** Preview shows the matched route early in the request lifecycle.

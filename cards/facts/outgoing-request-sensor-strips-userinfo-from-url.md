---
title: OutgoingRequestSensor strips userinfo from recorded url
tags: [sensor, outgoing-request, http, scrubbing]
status: verified 2026-11-10 auto
source: [src/Sensors/OutgoingRequestSensor.php]
as_of: 273b424ce 2026-11-10
---
OutgoingRequestSensor calls withUserInfo('') on the URI before recording it — userinfo (username/password) is never stored.

**Why:** Prevents accidental credential leakage in the telemetry payload.

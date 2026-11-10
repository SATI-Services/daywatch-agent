---
title: NotificationSensor failed flag always false — no NotificationFailed event exists
tags: [sensors, notification]
status: verified 2026-11-10 auto
source: [src/Sensors/NotificationSensor.php]
as_of: ebeca95d9 2026-11-10
---
NotificationSensor records failed=false unconditionally. Laravel dispatches no NotificationFailed event to observe.

**Why:** No Laravel event exists for notification send failures.

---

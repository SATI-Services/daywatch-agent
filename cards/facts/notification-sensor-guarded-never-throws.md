---
title: NotificationSensor every accessor guarded and whole path try/caught, never throws
tags: [sensors, notification, cardinal-rule]
status: verified 2026-11-10 auto
source: [src/Sensors/NotificationSensor.php]
as_of: ebeca95d9 2026-11-10
---
NotificationSensor guards every property access (null coalesce, type checks). The entire sending() and sent() path is try/caught. Throwables are swallowed; telemetry loss accepted.

**Why:** Notification operations must never fail loudly into the host app (CARDINAL RULE).

---

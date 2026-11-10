---
title: NotificationSensor records channel name and notification class
tags: [sensors, notification]
status: verified 2026-11-10 auto
source: [src/Sensors/NotificationSensor.php]
as_of: ebeca95d9 2026-11-10
---
NotificationSensor reads channel from event.channel (defaults to empty string) and class via get_class($notification) (defaults to empty string if not an object).

**Why:** Identifies which notification driver and which Notification class were used.

---

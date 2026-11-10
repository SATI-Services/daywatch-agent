---
title: NotificationSensor pairs NotificationSending and NotificationSent via spl_object_id
tags: [sensors, notification]
status: verified 2026-11-10 auto
source: [src/Sensors/NotificationSensor.php]
as_of: ebeca95d9 2026-11-10
---
NotificationSensor stashes NotificationSending timestamp keyed by spl_object_id($notification), then reads it on NotificationSent. Unpaired Sent uses current time as start, yielding 0 duration.

**Why:** Object identity is stable across event pair without parsing notification headers.

---

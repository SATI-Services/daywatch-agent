---
title: MailSensor pairs MessageSending and MessageSent events via spl_object_id
tags: [sensors, mail]
status: verified 2026-11-10 auto
source: [src/Sensors/MailSensor.php]
as_of: ebeca95d9 2026-11-10
---
MailSensor stashes MessageSending timestamp keyed by spl_object_id($message), then reads it back on MessageSent to compute duration. Unpaired Sent uses current time as start.

**Why:** Object identity is stable across event pair without parsing message headers.

---

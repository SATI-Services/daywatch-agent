---
title: MailSensor hardcodes failed=false on every mail record
tags: [sensors, mail]
status: verified 2026-11-10 auto
source: [src/Sensors/MailSensor.php]
as_of: ebeca95d9 2026-11-10
---
`MailSensor::sent()` builds its `MailRecord` with `failed: false` unconditionally. The sensor defines only `sending` and `sent`, which pair `MessageSending` → `MessageSent` into a `mail` record.

**Why:** There is no failure path in the sensor, so a `mail` record never reports a failed send.

---
title: MailSensor records recipient counts only, never addresses
tags: [sensors, mail, privacy]
status: verified 2026-11-10 auto
source: [src/Sensors/MailSensor.php]
as_of: ebeca95d9 2026-11-10
---
MailSensor reads getTo(), getCc(), getBcc() only to count recipients; recipient addresses are never read or transmitted.

**Why:** Addresses may be sensitive; counts are all the operational signal needed.

---

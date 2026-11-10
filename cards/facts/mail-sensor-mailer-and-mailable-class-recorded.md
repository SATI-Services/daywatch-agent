---
title: MailSensor records mailer name and Mailable class from MessageSent.data
tags: [sensors, mail]
status: verified 2026-11-10 auto
source: [src/Sensors/MailSensor.php]
as_of: ebeca95d9 2026-11-10
---
MailSensor reads mailer and __laravel_mailable from MessageSent.data array, defaulting to empty string if missing or not an array.

**Why:** Identifies which mailer driver and which Mailable class were used.

---

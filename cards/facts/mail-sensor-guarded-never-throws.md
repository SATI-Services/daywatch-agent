---
title: MailSensor guards every accessor and try/catches sending()/sent(), never throws
tags: [sensors, mail, cardinal-rule]
status: verified 2026-11-10 auto
source: [src/Sensors/MailSensor.php]
as_of: ebeca95d9 2026-11-10
---
MailSensor guards every property and method access: `method_exists` before calling `getSubject`, `getTo`/`getCc`/`getBcc`, `getAttachments`, and `is_countable` before `count`. The whole `sending()` and `sent()` path is try/caught; Throwables are swallowed and telemetry loss is accepted.

**Why:** Mail operations must never fail loudly into the host app (CARDINAL RULE).

---
title: MailSensor records attachment count, not attachment content
tags: [sensors, mail, privacy]
status: verified 2026-11-10 auto
source: [src/Sensors/MailSensor.php]
as_of: ebeca95d9 2026-11-10
---
MailSensor calls getAttachments() only to count them; attachment content is never read or transmitted.

**Why:** Attachment names or content may be sensitive; count is sufficient for telemetry.

---

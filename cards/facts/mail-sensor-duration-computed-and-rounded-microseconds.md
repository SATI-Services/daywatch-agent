---
title: MailSensor duration computed and rounded to microseconds
tags: [sensors, mail, timing]
status: verified 2026-11-10 auto
source: [src/Sensors/MailSensor.php]
as_of: ebeca95d9 2026-11-10
---
In `sent()`, MailSensor computes the `mail` record's `duration` as `(int) round(($now - $start) * 1_000_000)` and floors it to 0 when the result is negative.

**Why:** The record carries send timing in integer microseconds; negative results are floored to 0 rather than recorded.

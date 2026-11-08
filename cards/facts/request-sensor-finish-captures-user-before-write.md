---
title: RequestSensor.finish() captures user via UserSensor before writing the record
tags: [sensors, request, user]
status: verified 2026-11-08 auto
source: [src/Sensors/RequestSensor.php]
as_of: 16b149588 2026-11-08
---
In `RequestSensor::finish()`, `$this->users->capture()` is called after the `RequestRecord` is constructed and before `$core->write($record->toArray())`; `$core->finishExecution()` follows the write.

**Why:** The capture sits between record assembly and the write, so the user capture happens in-request, before the record is written out.

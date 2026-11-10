---
title: LogSensor swallows all throws; never throws into host app
tags: [safety, sensors]
status: verified 2026-11-10 auto
source: [src/Sensors/LogSensor.php]
as_of: 72d7040e7 2026-11-09
---
`LogSensor::handle()` catches `Throwable` and discards it: telemetry loss is acceptable, logging must never fail loudly. `encodeContext()` likewise returns `{}` on any throw.

**Why:** The cardinal rule is that the handler never throws into the host app; it is also pure — it never logs, which would re-enter this sensor and loop.

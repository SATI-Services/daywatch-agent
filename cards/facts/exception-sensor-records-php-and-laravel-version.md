---
title: ExceptionSensor stamps phpVersion and laravelVersion on every exception record
tags: [sensors, exceptions, records]
status: verified 2026-11-08 auto
source: [src/Sensors/ExceptionSensor.php]
as_of: 16b149588 2026-11-08
---
ExceptionSensor passes `phpVersion` (`PHP_VERSION`) and `laravelVersion` to every `ExceptionRecord` it builds.

`laravelVersion` is `(string) app()->version()`, or `''` when `app()` does not exist or throws.

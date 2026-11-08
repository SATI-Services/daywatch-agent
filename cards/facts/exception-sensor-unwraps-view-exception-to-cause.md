---
title: ExceptionSensor unwraps a ViewException to its cause only when it has one
tags: [sensors, exceptions, laravel]
status: verified 2026-11-08 auto
source: [src/Sensors/ExceptionSensor.php]
as_of: 16b149588 2026-11-08
---
The private `ExceptionSensor::unwrap()` returns `$e->getPrevious()` only when `$e` is an `Illuminate\View\ViewException` **and** `getPrevious() !== null`; in every other case it returns `$e` unchanged. It runs before the `exception` record is built.

**Why:** ViewException wraps the real cause; reporting the cause is more useful.

---
title: RequestSensor.finish() runs from whenRequestLifecycleIsLongerThan, else RequestHandled fallback
tags: [request-sensor, lifecycle]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
On attach, SensorManager checks the HTTP kernel for `whenRequestLifecycleIsLongerThan()`. If present, it registers it with a -1ms threshold so the handler always runs at request termination, calling `RequestSensor::finish($request, $response)` only when both `$request` and `$response` are objects (exceptions swallowed). If absent, a listener on `Illuminate\Foundation\Http\Events\RequestHandled` calls `finish($event->request, $event->response)` instead.

**Why:** The -1ms threshold makes the hook fire at every request termination; the RequestHandled listener is the fallback for kernels without the lifecycle hook.

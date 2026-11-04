---
title: SensorManager.register() makes 11 guarded calls attaching 12 sensor classes
tags: [sensors, wiring]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: 961f8e274 2026-11-04
---
`SensorManager::register()` makes 11 `safely()`-guarded calls attaching 12 sensor classes: RequestSensor, QuerySensor, ExceptionSensor, CacheEventSensor, LogSensor, MailSensor, NotificationSensor, OutgoingRequestSensor, QueuedJobSensor, JobAttemptSensor, CommandSensor, ScheduledTaskSensor. `attachQueueSensors()` is one call covering both queue sensors.

Most are wired as Laravel event listeners via `on()`, but RequestSensor also prepends the `CaptureRequest` middleware (plus a request-lifecycle finish hook) and OutgoingRequestSensor registers `Http::globalMiddleware` — that attach is additionally gated on `class_exists(Http::class)`.

**Why:** Counting attach methods rather than sensors miscounts; and the queue pair plus the Http-gated outgoing sensor are the two attaches that do not follow the one-sensor-one-call shape.

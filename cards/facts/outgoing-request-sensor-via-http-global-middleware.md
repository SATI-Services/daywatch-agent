---
title: OutgoingRequestSensor attaches via Http::globalMiddleware only when enabled and Http exists
tags: [outgoing-request-sensor, http]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
`attachOutgoingRequestSensor()` returns early unless the sensor is enabled and the `Illuminate\Support\Facades\Http` facade class exists. Otherwise it resolves the singleton once via `$this->app->make(OutgoingRequestSensor::class)` and calls `Http::globalMiddleware($sensor->guzzleMiddleware())`.

Enabled means config `daywatch.sensors.{class}.enabled` (default true) — except that a legacy `daywatch.filtering.ignore_outgoing_requests` key still wins when the host config has it, and its meaning is inverted (`true` = disabled). The package no longer ships that key.

**Why:** Registering at the Guzzle layer means every request made through the `Http` facade passes through the sensor's middleware.

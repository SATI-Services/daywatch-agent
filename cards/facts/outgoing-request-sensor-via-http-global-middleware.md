---
title: OutgoingRequestSensor attaches via Http::globalMiddleware only when enabled and Http exists
tags: [outgoing-request-sensor, http]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: 961f8e274 2026-11-04
---
`attachOutgoingRequestSensor()` returns early unless the sensor is enabled AND `class_exists(Illuminate\Support\Facades\Http::class)`. Otherwise it resolves the singleton once via `$this->app->make(OutgoingRequestSensor::class)` and calls `Http::globalMiddleware($sensor->guzzleMiddleware())`.

"Enabled" means config `daywatch.sensors.Daywatch\Agent\Sensors\OutgoingRequestSensor.enabled` (default true) — except that a legacy `daywatch.filtering.ignore_outgoing_requests` key still wins when the host config has it, and its sense is inverted (`true` = disabled). The package no longer ships that key, so it can only come from the host's own config.

**Why:** Registering at the Guzzle layer means every request made through the `Http` facade passes through the sensor's middleware; the inverted legacy key is what silently disables it in hosts with a pre-sensors-block published config.

---
title: ExceptionSensor attaches via ExceptionHandler::reportable(), reporting with $e, true
tags: [exception-sensor, lifecycle]
status: verified 2026-11-04 auto
source: [src/SensorManager.php]
as_of: f7f795e35 2026-11-04
---
In `SensorManager::attachExceptionSensor()`, after the `enabled(ExceptionSensor::class)` check passes, the sensor is resolved and registered when the handler has the method:

```php
$handler = $this->app->make(ExceptionHandler::class);

if (method_exists($handler, 'reportable')) {
    $handler->reportable(function (Throwable $e) use ($sensor): void {
        try {
            $sensor->report($e, true);
        } catch (Throwable) {
        }
    });
}
```

If the handler has no `reportable` method, nothing is attached. The callback body is guarded, so a throwing sensor cannot reach the host app.

**Why:** Catches exceptions at the framework's central reporting point.

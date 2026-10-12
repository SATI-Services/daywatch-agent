<?php

declare(strict_types=1);

use Daywatch\Agent\Http\Middleware\CaptureRequest;
use Daywatch\Agent\SensorManager;
use Daywatch\Agent\Sensors\CacheEventSensor;
use Daywatch\Agent\Sensors\JobAttemptSensor;
use Daywatch\Agent\Sensors\QuerySensor;
use Daywatch\Agent\Sensors\QueuedJobSensor;
use Daywatch\Agent\Sensors\RequestSensor;
use Illuminate\Contracts\Http\Kernel as HttpKernel;

/**
 * The per-sensor `enabled` gate (config `daywatch.sensors.{class}.enabled`)
 * decides whether a sensor's listeners are attached at all. The provider boot
 * already registered one manager with defaults, so each test registers a
 * FRESH manager under the overridden config and diffs dispatcher listener
 * counts — a disabled sensor attaches nothing.
 */
function listenerCount(string $event): int
{
    return count(app('events')->getListeners($event));
}

it('attaches a sensor by default', function () {
    $before = listenerCount('Illuminate\Cache\Events\CacheHit');

    (new SensorManager(app()))->register();

    expect(listenerCount('Illuminate\Cache\Events\CacheHit'))->toBe($before + 1);
});

it('attaches nothing for a sensor whose enabled flag is off', function () {
    config()->set('daywatch.sensors.'.CacheEventSensor::class.'.enabled', false);
    config()->set('daywatch.sensors.'.QuerySensor::class.'.enabled', false);

    $cache = listenerCount('Illuminate\Cache\Events\CacheHit');
    $query = listenerCount('Illuminate\Database\Events\QueryExecuted');
    $log = listenerCount('Illuminate\Log\Events\MessageLogged');

    (new SensorManager(app()))->register();

    expect(listenerCount('Illuminate\Cache\Events\CacheHit'))->toBe($cache)
        ->and(listenerCount('Illuminate\Database\Events\QueryExecuted'))->toBe($query)
        ->and(listenerCount('Illuminate\Log\Events\MessageLogged'))->toBe($log + 1); // unaffected sensors still attach
});

it('gates the queued-job and job-attempt sensors independently', function () {
    config()->set('daywatch.sensors.'.QueuedJobSensor::class.'.enabled', false);

    $queueing = listenerCount('Illuminate\Queue\Events\JobQueueing');
    $processing = listenerCount('Illuminate\Queue\Events\JobProcessing');

    (new SensorManager(app()))->register();

    expect(listenerCount('Illuminate\Queue\Events\JobQueueing'))->toBe($queueing)
        ->and(listenerCount('Illuminate\Queue\Events\JobProcessing'))->toBe($processing + 1);

    config()->set('daywatch.sensors.'.QueuedJobSensor::class.'.enabled', true);
    config()->set('daywatch.sensors.'.JobAttemptSensor::class.'.enabled', false);

    $queueing = listenerCount('Illuminate\Queue\Events\JobQueueing');
    $processing = listenerCount('Illuminate\Queue\Events\JobProcessing');

    (new SensorManager(app()))->register();

    expect(listenerCount('Illuminate\Queue\Events\JobQueueing'))->toBe($queueing + 1)
        ->and(listenerCount('Illuminate\Queue\Events\JobProcessing'))->toBe($processing);
});

it('honours the legacy filtering kill-switches from pre-sensors published configs', function () {
    config()->set('daywatch.filtering.ignore_cache_events', true);
    config()->set('daywatch.filtering.ignore_queries', true);
    config()->set('daywatch.filtering.ignore_outgoing_requests', true);

    $cache = listenerCount('Illuminate\Cache\Events\CacheHit');
    $query = listenerCount('Illuminate\Database\Events\QueryExecuted');

    (new SensorManager(app()))->register();

    expect(listenerCount('Illuminate\Cache\Events\CacheHit'))->toBe($cache)
        ->and(listenerCount('Illuminate\Database\Events\QueryExecuted'))->toBe($query);
});

it('lets a legacy key win over the new enabled flag while both are present', function () {
    config()->set('daywatch.filtering.ignore_cache_events', true);
    config()->set('daywatch.sensors.'.CacheEventSensor::class.'.enabled', true);

    $cache = listenerCount('Illuminate\Cache\Events\CacheHit');

    (new SensorManager(app()))->register();

    expect(listenerCount('Illuminate\Cache\Events\CacheHit'))->toBe($cache);
});

it('keeps every sensor at shipped defaults when a host defines a partial sensors block', function () {
    // Shallow top-level merge: a host-defined `sensors` key REPLACES the
    // package's whole block. Every read site must fall back to shipped
    // defaults per sensor, so nothing turns off by accident.
    config()->set('daywatch.sensors', [
        CacheEventSensor::class => ['groups' => ['#^sys_setting_.*$#' => 'sys_setting:*']],
    ]);

    $cache = listenerCount('Illuminate\Cache\Events\CacheHit');
    $query = listenerCount('Illuminate\Database\Events\QueryExecuted');
    $log = listenerCount('Illuminate\Log\Events\MessageLogged');

    (new SensorManager(app()))->register();

    expect(listenerCount('Illuminate\Cache\Events\CacheHit'))->toBe($cache + 1)
        ->and(listenerCount('Illuminate\Database\Events\QueryExecuted'))->toBe($query + 1)
        ->and(listenerCount('Illuminate\Log\Events\MessageLogged'))->toBe($log + 1);
});

it('stays enabled when a legacy kill-switch is present but false (old config defaults)', function () {
    config()->set('daywatch.filtering.ignore_cache_events', false);
    config()->set('daywatch.filtering.ignore_queries', false);
    config()->set('daywatch.filtering.ignore_outgoing_requests', false);

    $cache = listenerCount('Illuminate\Cache\Events\CacheHit');
    $query = listenerCount('Illuminate\Database\Events\QueryExecuted');

    (new SensorManager(app()))->register();

    expect(listenerCount('Illuminate\Cache\Events\CacheHit'))->toBe($cache + 1)
        ->and(listenerCount('Illuminate\Database\Events\QueryExecuted'))->toBe($query + 1);
});

/**
 * The kernel's global middleware list (Foundation kernel keeps it protected;
 * Testbench's kernel has no accessor).
 *
 * @return array<int, string>
 */
function kernelMiddleware(object $kernel): array
{
    $property = new ReflectionProperty($kernel, 'middleware');
    $property->setAccessible(true);

    return $property->getValue($kernel);
}

/** Strip the boot-time prepend so a fresh register's own behaviour is observed. */
function stripCaptureMiddleware(object $kernel): void
{
    $property = new ReflectionProperty($kernel, 'middleware');
    $property->setAccessible(true);
    $property->setValue($kernel, array_values(array_filter(
        $property->getValue($kernel),
        fn (string $middleware): bool => $middleware !== CaptureRequest::class,
    )));
}

it('prepends the request-capture middleware to the HTTP kernel', function () {
    // Listener diffs cover the event half of attachRequestSensor; the other
    // half is the global middleware — without it no request execution ever
    // starts, so pin the prepend itself. (The kernel dedupes prepends, so the
    // boot-time copy is stripped first.)
    $kernel = app(HttpKernel::class);
    stripCaptureMiddleware($kernel);

    (new SensorManager(app()))->register();

    expect(kernelMiddleware($kernel)[0] ?? null)->toBe(CaptureRequest::class);
});

it('does not prepend the middleware when the request sensor is disabled', function () {
    config()->set('daywatch.sensors.'.RequestSensor::class.'.enabled', false);

    $kernel = app(HttpKernel::class);
    stripCaptureMiddleware($kernel);

    (new SensorManager(app()))->register();

    expect(kernelMiddleware($kernel))->not->toContain(CaptureRequest::class);
});

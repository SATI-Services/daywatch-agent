<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Core;
use Daywatch\Agent\Sensors\QuerySensor;

/**
 * End-to-end config wiring: the container-built Core must honour both sampling
 * layers — head sampling (`daywatch.sampling.*`, one decision per execution)
 * and per-sensor record sampling (`daywatch.sensors.{class}.sample`, a
 * per-record roll within the execution).
 *
 * The provider's boot resolves Core once (via the SensorManager), so a config
 * override inside a test body lands too late for the boot-built instance —
 * each override test FORGETS the singleton and re-resolves it, exercising the
 * real provider factory against the overridden config.
 */
$rebuiltCore = function (): Core {
    app()->forgetInstance(Core::class);

    return app(Core::class);
};

it('samples every request at the shipped defaults', function () use ($rebuiltCore) {
    $core = $rebuiltCore();
    $core->prepareForRequest();

    expect($core->shouldSample())->toBeTrue();
});

it('wires the head request sample rate into the container-built Core', function () use ($rebuiltCore) {
    config()->set('daywatch.sampling.requests', 0.0);

    $core = $rebuiltCore();
    $core->prepareForRequest();

    expect($core->shouldSample())->toBeFalse();
});

it('wires the command and scheduled-task head sample rates', function () use ($rebuiltCore) {
    config()->set('daywatch.sampling.commands', 0.0);
    config()->set('daywatch.sampling.scheduled_tasks', 0.0);

    $core = $rebuiltCore();

    $core->prepareForCommand('demo:command');
    expect($core->shouldSample())->toBeFalse();

    $core->prepareForScheduledTask('demo:task');
    expect($core->shouldSample())->toBeFalse();
});

it('wires the exception re-roll rate into the container-built Core', function () use ($rebuiltCore) {
    config()->set('daywatch.sampling.requests', 0.0);
    config()->set('daywatch.sampling.exceptions', 1.0);

    $core = $rebuiltCore();
    $core->prepareForRequest();
    expect($core->shouldSample())->toBeFalse();

    $core->recordException(['t' => 'exception'], 'X: boom');
    expect($core->shouldSample())->toBeTrue();
});

it('wires a sensor sample rate into the container-built Core', function () use ($rebuiltCore) {
    config()->set('daywatch.sensors.'.QuerySensor::class.'.sample', 0.0);

    $core = $rebuiltCore();
    $core->prepareForRequest();
    $core->recordQuery(['t' => 'query']);

    expect($core->counters()['queries'])->toBe(1) // still counted…
        ->and(app(RecordsBuffer::class)->count())->toBe(0); // …but not buffered
});

it('keeps sibling sensors at the default rate when one is sampled down', function () use ($rebuiltCore) {
    config()->set('daywatch.sensors.'.QuerySensor::class.'.sample', 0.0);

    $core = $rebuiltCore();
    $core->prepareForRequest();
    $core->recordCacheEvent(['t' => 'cache-event']);
    $core->recordLog(['t' => 'log']);

    expect(app(RecordsBuffer::class)->count())->toBe(2);
});

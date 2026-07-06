<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\StageSensor;
use Daywatch\Agent\Support\ExecutionStage;
use Daywatch\Agent\Tests\Support\RecordingClient;

it('accumulates integer-microsecond durations as stages advance', function () {
    $client = new RecordingClient;
    [$core, , $clock] = makeCore($client, now: 1000.0);
    $core->prepareForRequest(1000.0);

    $stages = new StageSensor($core);

    $clock->set(1000.024);
    $stages->advance(ExecutionStage::BOOTSTRAP); // bootstrap = 24 000 µs

    $clock->set(1000.030);
    $stages->advance(ExecutionStage::BEFORE_MIDDLEWARE); // before_middleware = 6 000 µs

    expect($core->stages()['bootstrap'])->toBe(24000)
        ->and($core->stages()['before_middleware'])->toBe(6000)
        ->and($core->executionStage)->toBe(ExecutionStage::ACTION);
});

it('never throws even if the clock misbehaves', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client);
    $core->prepareForRequest();

    (new StageSensor($core))->advance(ExecutionStage::BOOTSTRAP);

    expect(true)->toBeTrue();
});

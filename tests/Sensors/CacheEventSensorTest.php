<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\CacheEventSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\RetrievingKey;

it('maps a CacheHit to a cache-event record', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new CacheEventSensor($core))->handle(new CacheHit('redis', 'users:1', 'value'));

    $record = $buffer->all()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('cache-event')
        ->and($record['type'])->toBe('hit')
        ->and($record['store'])->toBe('redis')
        ->and($record['key'])->toBe('users:1')
        ->and($record['duration'])->toBe(0)
        ->and($record['ttl'])->toBe(0)
        ->and($record['_group'])->toBe(Group::cache('redis', 'users:1'))
        ->and($record['trace_id'])->toBe($core->traceId)
        ->and($record['execution_source'])->toBe('request');
});

it('maps a CacheMissed to a miss record', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new CacheEventSensor($core))->handle(new CacheMissed('redis', 'users:2'));

    expect($buffer->all()[0]['type'])->toBe('miss');
});

it('records the ttl on a KeyWritten write', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new CacheEventSensor($core))->handle(new KeyWritten('redis', 'users:3', 'value', 3600));

    $record = $buffer->all()[0];

    expect($record['type'])->toBe('write')
        ->and($record['ttl'])->toBe(3600)
        ->and($record['_group'])->toBe(Group::cache('redis', 'users:3'));
});

it('pairs a start event with its completion to compute a microsecond duration', function () {
    $client = new RecordingClient;
    [$core, $buffer, $clock] = makeCore($client, requestRate: 1.0, now: 1000.0);
    $core->prepareForRequest();

    $sensor = new CacheEventSensor($core);

    $sensor->handle(new RetrievingKey('redis', 'users:4')); // start @ 1000.0
    $clock->advance(0.0025); // +2.5ms
    $sensor->handle(new CacheHit('redis', 'users:4', 'value')); // completion @ 1002.5ms

    $record = $buffer->all()[0];

    expect($record['duration'])->toBe(2500) // 2.5ms → µs
        ->and($record['type'])->toBe('hit')
        ->and($record['timestamp'])->toEqualWithDelta(1000.0, 0.0000001); // start timestamp
});

it('records duration 0 when no start event was seen', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new CacheEventSensor($core))->handle(new CacheHit('redis', 'orphan', 'value'));

    expect($buffer->all()[0]['duration'])->toBe(0);
});

it('increments the cache_events counter', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new CacheEventSensor($core))->handle(new CacheHit('redis', 'users:5', 'value'));

    expect($core->counters()['cache_events'])->toBe(1);
});

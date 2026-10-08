<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Core;
use Daywatch\Agent\Sensors\CacheEventSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\KeyGrouper;
use Daywatch\Agent\Support\Patterns;
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

it('ignores cache keys matching the ignore patterns', function () {
    [$core, $buffer] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new CacheEventSensor($core, new Patterns(['*illuminate:*']));

    $sensor->handle(new CacheHit('redis', 'illuminate:queue:restart', 'value'));
    $sensor->handle(new CacheMissed('redis', 'laravel_cache_illuminate:cooldown:x'));
    $sensor->handle(new CacheHit('redis', 'users:1', 'value'));

    expect($buffer->all())->toHaveCount(1)
        ->and($buffer->all()[0]['key'])->toBe('users:1');
});

it('does not hold pending start events for ignored keys', function () {
    [$core, $buffer] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new CacheEventSensor($core, new Patterns(['*illuminate:*']));

    $sensor->handle(new RetrievingKey('redis', 'illuminate:queue:restart'));
    $sensor->handle(new CacheHit('redis', 'illuminate:queue:restart', 'value'));

    expect($buffer->all())->toBeEmpty();
});

it('records every key when no ignore patterns are configured', function () {
    [$core, $buffer] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    (new CacheEventSensor($core, new Patterns([])))->handle(new CacheHit('redis', 'illuminate:queue:restart', 'v'));

    expect($buffer->all())->toHaveCount(1);
});

it('rewrites a grouped key to its label and collapses the _group hash', function () {
    [$core, $buffer] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new CacheEventSensor($core, new Patterns([]), new KeyGrouper([
        '#^sys_setting_.*$#' => 'sys_setting:*',
    ]));

    $sensor->handle(new CacheHit('redis', 'sys_setting_5753f25f3ab0b7e9442c9a528ab9efc1', 'v'));
    $sensor->handle(new CacheHit('redis', 'sys_setting_185eb1bfd1bb70a9806e52fb0d57a11f', 'v'));

    $records = $buffer->all();

    expect($records)->toHaveCount(2)
        ->and($records[0]['key'])->toBe('sys_setting:*')
        ->and($records[0]['_group'])->toBe(Group::cache('redis', 'sys_setting:*'))
        ->and($records[1]['_group'])->toBe($records[0]['_group']); // one dashboard row
});

it('passes an unmatched key through raw when groups are configured', function () {
    [$core, $buffer] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new CacheEventSensor($core, new Patterns([]), new KeyGrouper([
        '#^sys_setting_.*$#' => 'sys_setting:*',
    ]));

    $sensor->handle(new CacheHit('redis', 'users:1', 'v'));

    expect($buffer->all()[0]['key'])->toBe('users:1');
});

it('honours the first matching group pattern', function () {
    [$core, $buffer] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new CacheEventSensor($core, new Patterns([]), new KeyGrouper([
        '#^sys_setting_admin.*$#' => 'sys_setting:admin',
        '#^sys_setting_.*$#' => 'sys_setting:*',
    ]));

    $sensor->handle(new CacheHit('redis', 'sys_setting_admin_123', 'v'));

    expect($buffer->all()[0]['key'])->toBe('sys_setting:admin');
});

it('still pairs start and completion events by the raw key when grouping', function () {
    [$core, $buffer, $clock] = makeCore(new RecordingClient, requestRate: 1.0, now: 1000.0);
    $core->prepareForRequest();

    $sensor = new CacheEventSensor($core, new Patterns([]), new KeyGrouper([
        '#^sys_setting_.*$#' => 'sys_setting:*',
    ]));

    $sensor->handle(new RetrievingKey('redis', 'sys_setting_abc123')); // start @ 1000.0 (raw)
    $clock->advance(0.002); // +2ms
    $sensor->handle(new CacheHit('redis', 'sys_setting_abc123', 'v')); // completion @ 1002.0ms

    $record = $buffer->all()[0];

    expect($record['key'])->toBe('sys_setting:*')
        ->and($record['duration'])->toBe(2000)
        ->and($record['timestamp'])->toEqualWithDelta(1000.0, 0.0000001);
});

it('applies ignore patterns to the raw key, before grouping', function () {
    [$core, $buffer] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new CacheEventSensor($core, new Patterns(['*illuminate:*']), new KeyGrouper([
        '#^illuminate:.*$#' => 'illuminate:*',
    ]));

    $sensor->handle(new CacheHit('redis', 'illuminate:queue:restart', 'v'));

    expect($buffer->all())->toBeEmpty();
});

it('ignores framework keys by default through the container binding', function () {
    config()->set('daywatch.filtering.ignore_cache_keys', '*illuminate:*');

    $sensor = app(CacheEventSensor::class);

    app(Core::class)->prepareForRequest();

    $sensor->handle(new CacheHit('redis', 'illuminate:queue:restart', 'v'));
    $sensor->handle(new CacheHit('redis', 'users:1', 'v'));

    $records = app(RecordsBuffer::class)->all();

    expect(array_column($records, 'key'))->toBe(['users:1']);
});

it('applies the new sensors.ignore option through the container binding', function () {
    config()->set('daywatch.sensors.'.CacheEventSensor::class.'.ignore', '*secret*');
    app()->forgetInstance(CacheEventSensor::class);

    $sensor = app(CacheEventSensor::class);

    app(Core::class)->prepareForRequest();

    $sensor->handle(new CacheHit('redis', 'secret_token', 'v'));
    $sensor->handle(new CacheHit('redis', 'users:1', 'v'));

    $records = app(RecordsBuffer::class)->all();

    expect(array_column($records, 'key'))->toBe(['users:1']);
});

it('applies the sensors.groups option through the container binding', function () {
    config()->set('daywatch.sensors.'.CacheEventSensor::class.'.groups', [
        '#^sys_setting_.*$#' => 'sys_setting:*',
    ]);
    app()->forgetInstance(CacheEventSensor::class);

    $sensor = app(CacheEventSensor::class);

    app(Core::class)->prepareForRequest();

    $sensor->handle(new CacheHit('redis', 'sys_setting_abc123', 'v'));

    $record = app(RecordsBuffer::class)->all()[0];

    expect($record['key'])->toBe('sys_setting:*')
        ->and($record['_group'])->toBe(Group::cache('redis', 'sys_setting:*'));
});

it('keeps the shipped ignore default when a host defines only groups in a partial sensors block', function () {
    // Shallow top-level merge replaces the whole `sensors` block; the provider
    // must still default `ignore` to the shipped framework-noise patterns.
    config()->set('daywatch.sensors', [
        CacheEventSensor::class => ['groups' => ['#^sys_setting_.*$#' => 'sys_setting:*']],
    ]);
    app()->forgetInstance(CacheEventSensor::class);

    $sensor = app(CacheEventSensor::class);

    app(Core::class)->prepareForRequest();

    $sensor->handle(new CacheHit('redis', 'illuminate:queue:restart', 'v')); // still ignored
    $sensor->handle(new CacheHit('redis', 'sys_setting_abc123', 'v'));       // grouped
    $sensor->handle(new CacheHit('redis', 'users:1', 'v'));                  // raw

    $records = app(RecordsBuffer::class)->all();

    expect(array_column($records, 'key'))->toBe(['sys_setting:*', 'users:1']);
});

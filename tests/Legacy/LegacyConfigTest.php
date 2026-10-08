<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Core;
use Daywatch\Agent\SensorManager;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\LegacyPublishedConfig;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Database\Events\QueryExecuted;

/**
 * Upgrade safety: ingestion under a pre-`sensors` published config must behave
 * exactly as it did on 1.0.2 — everything records, framework noise stays
 * ignored, keys record raw with no grouping. See
 * tests/Support/LegacyPublishedConfig.php for the boot simulation.
 */
uses(LegacyPublishedConfig::class);
function legacyConnection(string $name = 'mysql'): object
{
    return new class($name)
    {
        public function __construct(private string $name) {}

        public function getName(): string
        {
            return $this->name;
        }
    };
}

it('keeps recording cache events and queries end-to-end', function () {
    // Boot attached every sensor through the legacy fallback path (the legacy
    // keys were present before the provider booted).
    app(Core::class)->prepareForRequest();

    event(new CacheHit('redis', 'users:1', 'v'));
    event(new CacheHit('redis', 'illuminate:queue:restart', 'v')); // framework noise
    event(new QueryExecuted('select * from "orders"', [], 1.0, legacyConnection()));
    event(new QueryExecuted('select * from "jobs" where "queue" = ?', [], 1.0, legacyConnection())); // framework noise

    $records = app(RecordsBuffer::class)->all();

    expect(array_column($records, 'key'))->toBe(['users:1'])
        ->and(array_column($records, 'sql'))->toBe(['select * from "orders"']);
});

it('records raw keys with no grouping, exactly as before', function () {
    app(Core::class)->prepareForRequest();

    event(new CacheHit('redis', 'sys_setting_5753f25f3ab0b7e9442c9a528ab9efc1', 'v'));

    $record = app(RecordsBuffer::class)->all()[0];

    expect($record['key'])->toBe('sys_setting_5753f25f3ab0b7e9442c9a528ab9efc1')
        ->and($record['_group'])->toBe(Group::cache('redis', 'sys_setting_5753f25f3ab0b7e9442c9a528ab9efc1'));
});

it('honours a legacy kill-switch the host actually set', function () {
    config()->set('daywatch.filtering.ignore_cache_events', true);

    // A fresh manager under this config attaches nothing (the gate inverts the
    // legacy kill-switch); the boot-attached listeners are an artifact of the
    // default boot config, so assert on a fresh registration instead.
    $before = count(app('events')->getListeners('Illuminate\Cache\Events\CacheHit'));

    (new SensorManager(app()))->register();

    expect(count(app('events')->getListeners('Illuminate\Cache\Events\CacheHit')))->toBe($before);
});

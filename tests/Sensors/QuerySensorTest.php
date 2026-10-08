<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Core;
use Daywatch\Agent\Sensors\QuerySensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Location;
use Daywatch\Agent\Support\Patterns;
use Daywatch\Agent\Tests\Support\RecordingClient;
use Illuminate\Database\Events\QueryExecuted;

beforeEach(fn () => Location::setBasePath('/nowhere-real')); // origin() → ['', 0], deterministic
afterEach(fn () => Location::setBasePath(null));

function fakeConnection(string $name = 'mysql'): object
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

it('maps a QueryExecuted event to a query record', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $sql = 'select * from `orders` where `id` = ? limit 1';
    $event = new QueryExecuted($sql, [1], 8.4, fakeConnection('mysql'));

    (new QuerySensor($core))->handle($event);

    $record = $buffer->all()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('query')
        ->and($record['sql'])->toBe($sql)
        ->and($record['duration'])->toBe(8400) // 8.4ms → µs
        ->and($record['connection'])->toBe('mysql')
        ->and($record['connection_type'])->toBe('read')
        ->and($record['_group'])->toBe(Group::query('mysql', $sql))
        ->and($record['file'])->toBe('')
        ->and($record['line'])->toBe(0)
        ->and($record['trace_id'])->toBe($core->traceId)
        ->and($record['execution_source'])->toBe('request');
});

it('records the query start timestamp (now − duration)', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0, now: 1000.0);
    $core->prepareForRequest();

    (new QuerySensor($core))->handle(new QueryExecuted('select 1', [], 8.4, fakeConnection()));

    expect($buffer->all()[0]['timestamp'])->toEqualWithDelta(1000.0 - 0.0084, 0.0000001);
});

it('classifies write statements as connection_type write', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new QuerySensor($core))->handle(
        new QueryExecuted('insert into users (email) values (?)', ['a@b.c'], 1.0, fakeConnection()),
    );

    expect($buffer->all()[0]['connection_type'])->toBe('write');
});

it('increments the queries counter', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new QuerySensor($core))->handle(new QueryExecuted('select 1', [], 1.0, fakeConnection()));

    expect($core->counters()['queries'])->toBe(1);
});

it('ignores queries whose SQL matches the ignore patterns', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new QuerySensor($core, new Patterns(['*jobs*', '*cache*', '*sessions*', '*batches*']));

    // The database-queue poll, in both grammar quoting styles, plus the other
    // framework-internal tables — all dropped; the application query is kept.
    $sensor->handle(new QueryExecuted('select * from "jobs" where "queue" = ?', [], 1.0, fakeConnection()));
    $sensor->handle(new QueryExecuted('select * from `jobs` where `queue` = ?', [], 1.0, fakeConnection()));
    $sensor->handle(new QueryExecuted('select * from "cache" where "key" = ? limit 1', [], 1.0, fakeConnection()));
    $sensor->handle(new QueryExecuted('select * from "cache_locks" where "key" = ? limit 1', [], 1.0, fakeConnection()));
    $sensor->handle(new QueryExecuted('delete from "sessions" where "last_activity" < ?', [], 1.0, fakeConnection()));
    $sensor->handle(new QueryExecuted('select * from "job_batches" where "id" = ?', [], 1.0, fakeConnection()));
    $sensor->handle(new QueryExecuted('select * from "orders"', [], 1.0, fakeConnection()));

    expect($buffer->all())->toHaveCount(1)
        ->and($buffer->all()[0]['sql'])->toBe('select * from "orders"')
        ->and($core->counters()['queries'])->toBe(1);
});

it('ignores queries matching a real glob pattern', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new QuerySensor($core, new Patterns(['insert into jobs*']));

    $sensor->handle(new QueryExecuted('insert into jobs (queue) values (?)', [], 1.0, fakeConnection()));
    $sensor->handle(new QueryExecuted('select * from jobs', [], 1.0, fakeConnection()));

    expect($buffer->all())->toHaveCount(1)
        ->and($buffer->all()[0]['sql'])->toBe('select * from jobs');
});

it('records every query when no ignore patterns are configured', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new QuerySensor($core, new Patterns([])))->handle(
        new QueryExecuted('select * from "jobs" where "queue" = ?', [], 1.0, fakeConnection()),
    );

    expect($buffer->all())->toHaveCount(1);
});

it('applies ignore_query_patterns through the container binding', function () {
    // Sensors are resolved once at boot, so re-resolve after overriding config
    // (forgetInstance) — this exercises the provider's config wiring itself.
    config()->set('daywatch.filtering.ignore_query_patterns', '*internal_*');
    app()->forgetInstance(QuerySensor::class);

    $sensor = app(QuerySensor::class);

    app(Core::class)->prepareForRequest();

    $sensor->handle(new QueryExecuted('select * from "internal_audit_log"', [], 1.0, fakeConnection()));
    $sensor->handle(new QueryExecuted('select * from "orders"', [], 1.0, fakeConnection()));

    $records = app(RecordsBuffer::class)->all();

    expect(array_column($records, 'sql'))->toBe(['select * from "orders"']);
});

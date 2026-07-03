<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\QuerySensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Location;
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

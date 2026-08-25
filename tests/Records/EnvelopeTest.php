<?php

declare(strict_types=1);

use Daywatch\Agent\Records\Counters;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Support\Truncate;
use Daywatch\Agent\Tests\Support\RecordingClient;

function fullEnvelope(): Envelope
{
    return new Envelope(
        timestamp: 1751446800.5,
        deploy: 'abc1234',
        server: 'demo-01',
        traceId: 'trace-1',
        user: '42',
        executionId: 'exec-1',
        executionSource: 'request',
        executionPreview: 'GET /orders/{order}',
        executionStage: 'action',
    );
}

it('pins the record schema version', function () {
    expect(Envelope::VERSION)->toBe(1);
});

it('emits the child head in wire order', function () {
    expect(fullEnvelope()->child('query', 'g1'))->toBe([
        'v' => 1,
        't' => 'query',
        'timestamp' => 1751446800.5,
        'deploy' => 'abc1234',
        'server' => 'demo-01',
        '_group' => 'g1',
        'trace_id' => 'trace-1',
        'execution_id' => 'exec-1',
        'execution_source' => 'request',
        'execution_preview' => 'GET /orders/{order}',
        'execution_stage' => 'action',
        'user' => '42',
    ]);
});

it('omits _group entirely for a child record with no grouping recipe (log)', function () {
    $head = fullEnvelope()->child('log');

    expect($head)->not->toHaveKey('_group')
        ->and(array_keys($head))->toBe([
            'v', 't', 'timestamp', 'deploy', 'server', 'trace_id',
            'execution_id', 'execution_source', 'execution_preview', 'execution_stage', 'user',
        ]);
});

it('emits the execution-root head with no execution_* self-reference', function () {
    expect(fullEnvelope()->execution('request', 'g2'))->toBe([
        'v' => 1,
        't' => 'request',
        'timestamp' => 1751446800.5,
        'deploy' => 'abc1234',
        'server' => 'demo-01',
        '_group' => 'g2',
        'trace_id' => 'trace-1',
        'user' => '42',
    ]);
});

it('emits the minimal head for a record that belongs to no trace', function () {
    expect(fullEnvelope()->minimal('user'))->toBe([
        'v' => 1,
        't' => 'user',
        'timestamp' => 1751446800.5,
        'deploy' => 'abc1234',
        'server' => 'demo-01',
    ]);
});

it('applies the tiny byte cap to every capped envelope field', function () {
    $long = str_repeat('x', Truncate::TINY + 50);

    $envelope = new Envelope(
        timestamp: 1.0,
        deploy: $long,
        server: $long,
        traceId: 'trace',
        user: $long,
        executionId: 'exec',
        executionSource: 'request',
        executionPreview: $long,
        executionStage: 'action',
    );

    $head = $envelope->child('query', 'g');

    expect(strlen($head['deploy']))->toBe(Truncate::TINY)
        ->and(strlen($head['server']))->toBe(Truncate::TINY)
        ->and(strlen($head['user']))->toBe(Truncate::TINY)
        ->and(strlen($head['execution_preview']))->toBe(Truncate::TINY);
});

it('snapshots the live execution off Core', function () {
    [$core] = makeCore(new RecordingClient);
    $core->prepareForRequest();
    $core->user('99');

    $envelope = Envelope::for($core);

    expect($envelope->deploy)->toBe('dep')
        ->and($envelope->server)->toBe('srv')
        ->and($envelope->traceId)->toBe($core->traceId)
        ->and($envelope->executionId)->toBe($core->executionId)
        ->and($envelope->executionSource)->toBe('request')
        ->and($envelope->executionStage)->toBe($core->executionStage)
        ->and($envelope->user)->toBe('99')
        ->and($envelope->timestamp)->toBe(1000.0); // the frozen clock
});

it('lets a sensor override the timestamp (records that start before they are seen)', function () {
    [$core] = makeCore(new RecordingClient);

    expect(Envelope::for($core, 123.5)->timestamp)->toBe(123.5);
});

it('emits the shared execution tail in wire order, zero-filling unwired counters', function () {
    $tail = Counters::tail(['queries' => 3, 'logs' => 1], 2048, 'X: boom', '{"a":1}');

    expect(array_keys($tail))->toBe([
        ...Counters::KEYS,
        'peak_memory_usage',
        'exception_preview',
        'context',
    ])
        ->and($tail['queries'])->toBe(3)
        ->and($tail['logs'])->toBe(1)
        ->and($tail['hydrated_models'])->toBe(0)
        ->and($tail['peak_memory_usage'])->toBe(2048)
        ->and($tail['exception_preview'])->toBe('X: boom')
        ->and($tail['context'])->toBe('{"a":1}');
});

it('caps the tail strings at their documented tiers', function () {
    $tail = Counters::tail(
        [],
        0,
        str_repeat('p', Truncate::TINY + 10),
        str_repeat('c', Truncate::TEXT + 10),
    );

    expect(strlen($tail['exception_preview']))->toBe(Truncate::TINY)
        ->and(strlen($tail['context']))->toBe(Truncate::TEXT);
});

it('keeps the envelope the single source of the shared wire fields', function () {
    // Structural guard for the mapping layout: no record DTO may re-inline the
    // envelope or the shared tail. If this fails, a new record type copied the
    // head instead of delegating to Envelope.
    $offenders = [];

    foreach (glob(__DIR__.'/../../src/Records/*Record.php') as $file) {
        $source = (string) file_get_contents($file);

        foreach (["'v' => ", "'t' => ", "'trace_id' =>", "'execution_id' =>", "'peak_memory_usage' =>"] as $needle) {
            if (str_contains($source, $needle)) {
                $offenders[] = basename($file).' inlines '.trim($needle);
            }
        }
    }

    expect($offenders)->toBe([]);
});

<?php

declare(strict_types=1);

use Daywatch\Agent\Tests\Support\RecordingClient;
use Illuminate\Support\Facades\Context;

it('samples every execution at rate 1.0', function () {
    [$core] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    expect($core->shouldSample())->toBeTrue();
});

it('samples no execution at rate 0.0', function () {
    [$core] = makeCore(new RecordingClient, requestRate: 0.0);
    $core->prepareForRequest();

    expect($core->shouldSample())->toBeFalse();
});

it('digests the buffer on a sampled execution', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);

    $core->prepareForRequest();
    $core->write(['t' => 'query', 'x' => 1]);
    $core->finishExecution();

    expect($client->sent)->toHaveCount(1)
        ->and($client->lastDecoded())->toBe([['t' => 'query', 'x' => 1]]);
});

it('discards the buffer on an unsampled execution', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 0.0);

    $core->prepareForRequest();
    $core->write(['t' => 'query']);
    $core->finishExecution();

    expect($client->sent)->toBe([]);
});

it('re-rolls sampling on report so errors escape a sampled-out trace', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 0.0, exceptionRate: 1.0);

    $core->prepareForRequest();
    expect($core->shouldSample())->toBeFalse();

    $core->recordException(['t' => 'exception'], 'X: boom');

    expect($core->shouldSample())->toBeTrue();

    $core->finishExecution();
    expect($client->sent)->toHaveCount(1);
});

it('buffers every record when no per-type sample rates are configured', function () {
    [$core, $buffer] = makeCore(new RecordingClient);

    $core->prepareForRequest();
    $core->recordQuery(['t' => 'query']);
    $core->recordCacheEvent(['t' => 'cache-event']);
    $core->recordOutgoingRequest(['t' => 'outgoing-request']);
    $core->recordLog(['t' => 'log']);
    $core->recordMail(['t' => 'mail']);
    $core->recordNotification(['t' => 'notification']);
    $core->recordQueuedJob(['t' => 'queued-job']);

    expect($buffer->count())->toBe(7);
});

it('drops a record type at sample rate 0.0 but still counts every event', function () {
    [$core, $buffer] = makeCore(new RecordingClient, recordSampleRates: ['query' => 0.0]);

    $core->prepareForRequest();
    $core->recordQuery(['t' => 'query']);
    $core->recordQuery(['t' => 'query']);

    expect($buffer->count())->toBe(0)
        ->and($core->counters()['queries'])->toBe(2); // the execution tail keeps the true count
});

it('samples record types independently', function () {
    [$core, $buffer] = makeCore(new RecordingClient, recordSampleRates: [
        'query' => 0.0,
        'cache_event' => 1.0,
    ]);

    $core->prepareForRequest();
    $core->recordQuery(['t' => 'query']);
    $core->recordCacheEvent(['t' => 'cache-event']);

    expect($buffer->all())->toBe([['t' => 'cache-event']])
        ->and($core->counters()['queries'])->toBe(1)
        ->and($core->counters()['cache_events'])->toBe(1);
});

it('drops every high-volume record type at rate 0.0', function () {
    [$core, $buffer] = makeCore(new RecordingClient, recordSampleRates: [
        'query' => 0.0,
        'cache_event' => 0.0,
        'outgoing_request' => 0.0,
        'log' => 0.0,
        'mail' => 0.0,
        'notification' => 0.0,
        'queued_job' => 0.0,
    ]);

    $core->prepareForRequest();
    $core->recordQuery(['t' => 'query']);
    $core->recordCacheEvent(['t' => 'cache-event']);
    $core->recordOutgoingRequest(['t' => 'outgoing-request']);
    $core->recordLog(['t' => 'log']);
    $core->recordMail(['t' => 'mail']);
    $core->recordNotification(['t' => 'notification']);
    $core->recordQueuedJob(['t' => 'queued-job']);

    expect($buffer->count())->toBe(0)
        ->and($core->counters()['queries'])->toBe(1)
        ->and($core->counters()['cache_events'])->toBe(1)
        ->and($core->counters()['outgoing_requests'])->toBe(1)
        ->and($core->counters()['logs'])->toBe(1)
        ->and($core->counters()['mail'])->toBe(1)
        ->and($core->counters()['notifications'])->toBe(1)
        ->and($core->counters()['jobs_queued'])->toBe(1);
});

it('keeps roughly the configured fraction of records at a mid rate', function () {
    mt_srand(4242); // deterministic roll sequence for this test only

    [$core, $buffer] = makeCore(new RecordingClient, recordSampleRates: ['query' => 0.25]);

    $core->prepareForRequest();
    for ($i = 0; $i < 1000; $i++) {
        $core->recordQuery(['t' => 'query']);
    }

    expect($core->counters()['queries'])->toBe(1000) // every event counted
        ->and($buffer->count())->toBeGreaterThan(150)->toBeLessThan(350); // ~25% kept
});

it('auto-digests when the buffer fills on a sampled execution', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0, bufferLimit: 2);

    $core->prepareForRequest();
    $core->write(['n' => 1]);
    $core->write(['n' => 2]); // hits the limit → auto-digest

    expect($client->sent)->toHaveCount(1)
        ->and($buffer->count())->toBe(0);
});

it('ring-drops the oldest records when the buffer fills unsampled', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 0.0, bufferLimit: 2);

    $core->prepareForRequest();
    $core->write(['n' => 1]);
    $core->write(['n' => 2]);
    $core->write(['n' => 3]);

    expect($client->sent)->toBe([])
        ->and($buffer->count())->toBe(2)
        ->and($buffer->all())->toBe([['n' => 2], ['n' => 3]]);
});

it('does not buffer while paused', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client);

    $core->prepareForRequest();
    $core->pause();
    $core->write(['n' => 1]);
    expect($buffer->count())->toBe(0);

    $core->resume();
    $core->write(['n' => 2]);
    expect($buffer->count())->toBe(1);
});

it('resets all per-execution state at the worker boundary', function () {
    [$core, $buffer] = makeCore(new RecordingClient, requestRate: 1.0);

    $core->prepareForRequest();
    $first = $core->traceId;
    $core->increment('queries', 3);
    $core->write(['n' => 1]);

    $core->reset();

    expect($core->traceId)->not->toBe($first)
        ->and($core->counters()['queries'])->toBe(0)
        ->and($buffer->count())->toBe(0)
        ->and($core->shouldSample())->toBeFalse(); // sampling forced off between executions
});

it('increments only known counters', function () {
    [$core] = makeCore(new RecordingClient);

    $core->increment('queries');
    $core->increment('queries', 2);
    $core->increment('not_a_counter');

    expect($core->counters()['queries'])->toBe(3)
        ->and($core->counters())->not->toHaveKey('not_a_counter');
});

it('adopts a propagated trace + sampling decision across a queue hop', function () {
    Context::addHidden('daywatch_trace_id', 'trace-from-upstream');
    Context::addHidden('daywatch_should_sample', true);
    Context::addHidden('daywatch_user_id', '99');

    [$core] = makeCore(new RecordingClient, requestRate: 0.0);
    $core->prepareForJob();

    expect($core->traceId)->toBe('trace-from-upstream')
        ->and($core->shouldSample())->toBeTrue()
        ->and($core->executionSource)->toBe('job')
        ->and($core->resolveUser())->toBe('99');
});

it('writes the propagation context keys when preparing a request', function () {
    [$core] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    expect(Context::getHidden('daywatch_trace_id'))->toBe($core->traceId)
        ->and(Context::getHidden('daywatch_should_sample'))->toBeTrue();
});

it('json-encodes records on digest and sends nothing for an empty buffer', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client);

    $core->digest();
    expect($client->sent)->toBe([]);

    $core->prepareForRequest();
    $core->write(['t' => 'x']);
    $core->digest();

    expect($client->sent)->toHaveCount(1)
        ->and($client->sent[0])->toBeJson();
});

it('swallows a transport that throws (cardinal rule)', function () {
    $client = new RecordingClient;
    $client->throwOnSend = true;
    [$core] = makeCore($client);

    $core->prepareForRequest();
    $core->write(['t' => 'x']);

    $core->digest(); // must not throw

    expect(true)->toBeTrue();
});

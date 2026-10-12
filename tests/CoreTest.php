<?php

declare(strict_types=1);

use Daywatch\Agent\Support\ContextPropagation;
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

it('leaves records unstamped when the execution is unsampled (rate 1.0 ≡ absent)', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);

    $core->prepareForRequest();
    $core->write(['t' => 'request', 'x' => 1]);
    $core->write(['t' => 'query']);
    $core->finishExecution();

    expect($client->lastDecoded())->toBe([['t' => 'request', 'x' => 1], ['t' => 'query']]);
});

it('stamps every digested record with the propagated sample rate', function () {
    Context::addHidden('daywatch_trace_id', 'trace-from-upstream');
    Context::addHidden('daywatch_should_sample', true);
    Context::addHidden('daywatch_sample_rate', 0.25);

    $client = new RecordingClient;
    [$core] = makeCore($client);
    $core->prepareForJob();

    $core->write(['t' => 'job-attempt']); // execution root: head rate only
    $core->write(['t' => 'query']); // gated type, roll 1.0 by default
    $core->finishExecution();

    expect($core->sampleRate())->toBe(0.25)
        ->and($client->lastDecoded())->toBe([
            ['t' => 'job-attempt', 'sample_rate' => 0.25],
            ['t' => 'query', 'sample_rate' => 0.25],
        ]);
});

it('multiplies the execution rate by the per-sensor roll on gated child records', function () {
    mt_srand(4242); // deterministic roll sequence for this test only

    Context::addHidden('daywatch_trace_id', 'trace-from-upstream');
    Context::addHidden('daywatch_should_sample', true);
    Context::addHidden('daywatch_sample_rate', 0.5);

    $client = new RecordingClient;
    [$core] = makeCore($client, recordSampleRates: ['query' => 0.5]);
    $core->prepareForJob();

    $core->write(['t' => 'job-attempt']);
    for ($i = 0; $i < 200; $i++) {
        $core->recordQuery(['t' => 'query']);
    }
    $core->finishExecution();

    $records = $client->lastDecoded();
    $queries = array_values(array_filter($records, fn (array $r): bool => $r['t'] === 'query'));

    expect($queries)->not->toBe([])
        ->and($records[0])->toBe(['t' => 'job-attempt', 'sample_rate' => 0.5])
        ->and(array_unique(array_column($queries, 'sample_rate')))->toBe([0.25]);
});

it('reports the head rate that dropped the execution, then the exceptions rate after a rescue', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 0.0, exceptionRate: 1.0);

    $core->prepareForRequest();
    expect($core->sampleRate())->toBe(0.0);

    $core->recordException(['t' => 'exception'], 'X: boom');

    // Rescued by the re-roll: records must weight at the EXCEPTIONS rate
    // (here 1.0 → nothing stamped), never the head rate that dropped them.
    expect($core->sampleRate())->toBe(1.0);

    $core->finishExecution();
    expect($client->lastDecoded())->toBe([['t' => 'exception']]);
});

it('stamps nothing for a forced sample() even when the head roll dropped the execution', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 0.0);

    $core->prepareForRequest();
    $core->sample();
    $core->write(['t' => 'request']);
    $core->finishExecution();

    expect($core->sampleRate())->toBe(1.0)
        ->and($client->lastDecoded())->toBe([['t' => 'request']]);
});

it('propagates the effective rate and rejects an out-of-range one on read', function () {
    [$core] = makeCore(new RecordingClient, requestRate: 1.0);
    $core->prepareForRequest();

    expect(Context::getHidden('daywatch_sample_rate'))->toBe(1.0);

    Context::addHidden('daywatch_sample_rate', 1.5);
    expect(ContextPropagation::read()['sample_rate'])->toBeNull();

    Context::addHidden('daywatch_sample_rate', 0.1);
    expect(ContextPropagation::read()['sample_rate'])->toBe(0.1);
});

it('stamps the COMBINED inclusion probability when the execution recorded an exception', function () {
    Context::addHidden('daywatch_trace_id', 'trace-from-upstream');
    Context::addHidden('daywatch_should_sample', true);
    Context::addHidden('daywatch_sample_rate', 0.5);

    $client = new RecordingClient;
    [$core] = makeCore($client, exceptionRate: 0.5);
    $core->prepareForJob();

    $core->write(['t' => 'job-attempt']);
    $core->recordException(['t' => 'exception'], 'X: boom');
    $core->finishExecution();

    // Kept by EITHER roll: π = 0.5 + (1−0.5)×0.5 = 0.75 — stamping the head
    // rate alone would over-weight this execution ~1.33× on error counts.
    expect($client->lastDecoded())->toBe([
        ['t' => 'job-attempt', 'sample_rate' => 0.75],
        ['t' => 'exception', 'sample_rate' => 0.75],
    ]);
});

it('combines the per-sensor roll with the exception-inclusive probability on gated records', function () {
    mt_srand(4242); // deterministic roll sequence for this test only

    Context::addHidden('daywatch_trace_id', 'trace-from-upstream');
    Context::addHidden('daywatch_should_sample', true);
    Context::addHidden('daywatch_sample_rate', 0.5);

    $client = new RecordingClient;
    [$core] = makeCore($client, exceptionRate: 0.5, recordSampleRates: ['query' => 0.5]);
    $core->prepareForJob();

    $core->write(['t' => 'job-attempt']);
    $core->recordException(['t' => 'exception'], 'X: boom');
    for ($i = 0; $i < 200; $i++) {
        $core->recordQuery(['t' => 'query']);
    }
    $core->finishExecution();

    $records = $client->lastDecoded();
    $queries = array_values(array_filter($records, fn (array $r): bool => $r['t'] === 'query'));

    expect($queries)->not->toBe([])
        ->and($records[0])->toBe(['t' => 'job-attempt', 'sample_rate' => 0.75])
        ->and(array_unique(array_column($queries, 'sample_rate')))->toBe([0.375]);
});

it('omits the stamp for exception executions when the combined probability reaches 1.0', function () {
    Context::addHidden('daywatch_trace_id', 'trace-from-upstream');
    Context::addHidden('daywatch_should_sample', true);
    Context::addHidden('daywatch_sample_rate', 0.5);

    $client = new RecordingClient;
    [$core] = makeCore($client, exceptionRate: 1.0);
    $core->prepareForJob();

    $core->write(['t' => 'job-attempt']);
    $core->recordException(['t' => 'exception'], 'X: boom');
    $core->finishExecution();

    // π = 0.5 + 0.5×1.0 = 1.0 → errors at the default exceptions rate stay
    // exact and read as unsampled downstream.
    expect($client->lastDecoded())->toBe([
        ['t' => 'job-attempt'],
        ['t' => 'exception'],
    ]);
});

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

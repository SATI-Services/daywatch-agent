<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\BatchBuffer;
use Daywatch\Agent\Support\FrozenClock;

it('strips brackets, comma-joins, and rewraps as {"records":[...]}', function () {
    $buffer = new BatchBuffer(new FrozenClock(1000.0));

    $buffer->add('[{"a":1}]');
    $buffer->add('[{"b":2},{"c":3}]');

    expect($buffer->flush())->toBe('{"records":[{"a":1},{"b":2},{"c":3}]}');
});

it('never re-parses — record bytes are concatenated verbatim', function () {
    $buffer = new BatchBuffer(new FrozenClock(1.0));
    $buffer->add('[{"sql":"a,b,c","nested":[1,2]}]');

    expect($buffer->flush())->toBe('{"records":[{"sql":"a,b,c","nested":[1,2]}]}');
});

it('tracks the accumulated size', function () {
    $buffer = new BatchBuffer(new FrozenClock(1.0));
    $buffer->add('[{"a":1}]');

    expect($buffer->size())->toBe(strlen('{"a":1}'));
});

it('returns null when flushing an empty buffer', function () {
    expect((new BatchBuffer(new FrozenClock(1.0)))->flush())->toBeNull();
});

it('ignores payloads that are not JSON arrays', function () {
    $buffer = new BatchBuffer(new FrozenClock(1.0));

    expect($buffer->add('PING'))->toBe(0)
        ->and($buffer->add('{"not":"array"}'))->toBe(0)
        ->and($buffer->isEmpty())->toBeTrue();
});

it('flushes on the size ceiling', function () {
    $buffer = new BatchBuffer(new FrozenClock(1.0), flushBytes: 10);

    expect($buffer->shouldFlush())->toBeFalse();
    $buffer->add('[{"aaaaaaaaaaaa":1}]'); // > 10 record bytes
    expect($buffer->shouldFlush())->toBeTrue();
});

it('flushes on the age ceiling', function () {
    $clock = new FrozenClock(1000.0);
    $buffer = new BatchBuffer($clock, flushBytes: 6_000_000, flushIntervalSeconds: 10);

    $buffer->add('[{"a":1}]');
    expect($buffer->shouldFlush())->toBeFalse();

    $clock->advance(10.0);
    expect($buffer->shouldFlush())->toBeTrue();
});

it('detects when an incoming payload would exceed the ceiling', function () {
    $buffer = new BatchBuffer(new FrozenClock(1.0), flushBytes: 20);
    $buffer->add('[{"a":1}]'); // 7 record bytes

    expect($buffer->wouldExceed(5))->toBeFalse()
        ->and($buffer->wouldExceed(15))->toBeTrue();
});

it('measures record bytes without buffering', function () {
    $buffer = new BatchBuffer(new FrozenClock(1.0));

    expect($buffer->measure('[{"a":1}]'))->toBe(strlen('{"a":1}'))
        ->and($buffer->isEmpty())->toBeTrue();
});

it('resets after a flush', function () {
    $clock = new FrozenClock(1.0);
    $buffer = new BatchBuffer($clock);
    $buffer->add('[{"a":1}]');
    $buffer->flush();

    expect($buffer->isEmpty())->toBeTrue()
        ->and($buffer->size())->toBe(0)
        ->and($buffer->shouldFlush())->toBeFalse();
});

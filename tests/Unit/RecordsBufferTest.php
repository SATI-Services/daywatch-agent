<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;

it('stores and counts records', function () {
    $buffer = new RecordsBuffer(500);
    $buffer->write(['t' => 'query']);
    $buffer->write(['t' => 'exception']);

    expect($buffer->count())->toBe(2)
        ->and($buffer->isFull())->toBeFalse();
});

it('reports full at the configured limit', function () {
    $buffer = new RecordsBuffer(2);
    $buffer->write(['a' => 1]);
    expect($buffer->isFull())->toBeFalse();
    $buffer->write(['b' => 2]);
    expect($buffer->isFull())->toBeTrue();
});

it('ring-drops the oldest records when trimmed to the limit', function () {
    $buffer = new RecordsBuffer(2);
    $buffer->write(['n' => 1]);
    $buffer->write(['n' => 2]);
    $buffer->write(['n' => 3]);

    $buffer->trimToLimit();

    expect($buffer->count())->toBe(2)
        ->and($buffer->all())->toBe([['n' => 2], ['n' => 3]]);
});

it('pull returns and empties the buffer', function () {
    $buffer = new RecordsBuffer;
    $buffer->write(['x' => 1]);

    expect($buffer->pull())->toBe([['x' => 1]])
        ->and($buffer->count())->toBe(0)
        ->and($buffer->pull())->toBe([]);
});

it('flush discards without returning', function () {
    $buffer = new RecordsBuffer;
    $buffer->write(['x' => 1]);
    $buffer->flush();

    expect($buffer->count())->toBe(0);
});

it('measures approximate bytes as records are written', function () {
    $buffer = new RecordsBuffer(500, 1_000_000);

    expect($buffer->bytes())->toBe(0);

    $buffer->write(['t' => 'query', 'sql' => str_repeat('x', 1000)]);

    // key allowances + the 1000-byte string, and nothing wildly off from it.
    expect($buffer->bytes())->toBeGreaterThan(1000)
        ->and($buffer->bytes())->toBeLessThan(1100);
});

it('counts nested arrays (stages/counters) toward the byte total', function () {
    $flat = new RecordsBuffer(500, 0);
    $nested = new RecordsBuffer(500, 0);

    $flat->write(['t' => 'request']);
    $nested->write(['t' => 'request', 'counters' => ['queries' => 1, 'logs' => 2]]);

    expect($nested->bytes())->toBeGreaterThan($flat->bytes());
});

it('reports full on the BYTE bound long before the record count is reached', function () {
    $buffer = new RecordsBuffer(500, 10_000);

    expect($buffer->isFull())->toBeFalse();

    // Two records well under the count limit but over the byte limit — the
    // memory bound a count-only buffer would miss entirely.
    $buffer->write(['sql' => str_repeat('x', 6000)]);
    expect($buffer->isFull())->toBeFalse();

    $buffer->write(['sql' => str_repeat('x', 6000)]);

    expect($buffer->isFull())->toBeTrue()
        ->and($buffer->count())->toBe(2);
});

it('ring-drops oldest records until the byte bound is satisfied', function () {
    $buffer = new RecordsBuffer(500, 10_000);

    $buffer->write(['id' => 'a', 'sql' => str_repeat('a', 6000)]);
    $buffer->write(['id' => 'b', 'sql' => str_repeat('b', 6000)]);
    $buffer->write(['id' => 'c', 'sql' => str_repeat('c', 6000)]);

    $buffer->trimToLimit();

    expect($buffer->count())->toBe(1)
        ->and($buffer->all()[0]['id'])->toBe('c')
        ->and($buffer->bytes())->toBeLessThan(10_000);
});

it('keeps a single oversized record rather than spinning on an unsatisfiable bound', function () {
    $buffer = new RecordsBuffer(500, 1000);

    $buffer->write(['sql' => str_repeat('x', 50_000)]);

    $buffer->trimToLimit();

    expect($buffer->count())->toBe(1);
});

it('leaves only the count bound when the byte bound is disabled', function () {
    $buffer = new RecordsBuffer(3, 0);

    for ($i = 0; $i < 4; $i++) {
        $buffer->write(['sql' => str_repeat('x', 1_000_000)]);
    }

    expect($buffer->isFull())->toBeTrue();

    $buffer->trimToLimit();

    expect($buffer->count())->toBe(3);
});

it('resets the byte total on pull and flush', function () {
    $buffer = new RecordsBuffer(500, 10_000);

    $buffer->write(['sql' => str_repeat('x', 500)]);
    expect($buffer->pull())->toHaveCount(1);
    expect($buffer->bytes())->toBe(0);

    $buffer->write(['sql' => str_repeat('x', 500)]);
    $buffer->flush();

    expect($buffer->bytes())->toBe(0)
        ->and($buffer->count())->toBe(0);
});

it('keeps the byte total accurate across mixed writes and trims', function () {
    $buffer = new RecordsBuffer(2, 0);

    $buffer->write(['sql' => str_repeat('x', 100)]);
    $buffer->write(['sql' => str_repeat('x', 100)]);
    $one = $buffer->bytes();

    $buffer->write(['sql' => str_repeat('x', 100)]);
    $buffer->trimToLimit();

    expect($buffer->count())->toBe(2)
        ->and($buffer->bytes())->toBe($one);
});

<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;

it('stores and counts records', function () {
    $buffer = new RecordsBuffer(500);
    $buffer->write(['t' => 'query']);
    $buffer->write(['t' => 'exception']);

    expect($buffer->count())->toBe(2)
        ->and($buffer->limit())->toBe(500)
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

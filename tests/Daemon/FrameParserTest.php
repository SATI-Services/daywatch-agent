<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\FrameParser;
use Daywatch\Agent\Ingest\Payload;

it('parses a single complete frame', function () {
    $parser = new FrameParser;
    $frame = Payload::frame('[{"t":"query"}]', 'abc1234');

    $frames = $parser->push($frame);

    expect($frames)->toHaveCount(1)
        ->and($frames[0]->version)->toBe('v1')
        ->and($frames[0]->tokenHash)->toBe('abc1234')
        ->and($frames[0]->payload)->toBe('[{"t":"query"}]')
        ->and($parser->errored())->toBeFalse();
});

it('preserves colons inside the JSON payload', function () {
    $parser = new FrameParser;
    $payload = '[{"url":"https://x.test:443/a","t":"query"}]';

    $frames = $parser->push(Payload::frame($payload, 'abc1234'));

    expect($frames[0]->payload)->toBe($payload);
});

it('parses pipelined frames in one chunk', function () {
    $parser = new FrameParser;
    $chunk = Payload::frame('[{"a":1}]', 'abc1234').Payload::frame('PING', 'abc1234');

    $frames = $parser->push($chunk);

    expect($frames)->toHaveCount(2)
        ->and($frames[0]->payload)->toBe('[{"a":1}]')
        ->and($frames[1]->isPing())->toBeTrue();
});

it('reassembles a frame split across chunks', function () {
    $parser = new FrameParser;
    $frame = Payload::frame('[{"a":1}]', 'abc1234');

    $first = $parser->push(substr($frame, 0, 5));
    $second = $parser->push(substr($frame, 5));

    expect($first)->toBe([])
        ->and($second)->toHaveCount(1)
        ->and($second[0]->payload)->toBe('[{"a":1}]');
});

it('recognises a PING frame', function () {
    $parser = new FrameParser;

    $frames = $parser->push(Payload::frame('PING', 'abc1234'));

    expect($frames[0]->isPing())->toBeTrue();
});

it('flags a non-numeric length prefix as an unrecoverable error', function () {
    $parser = new FrameParser;

    $frames = $parser->push('garbage:v1:abc1234:[]');

    expect($frames)->toBe([])
        ->and($parser->errored())->toBeTrue()
        ->and($parser->errorMessage())->toContain('non-numeric');
});

it('rejects a frame whose declared length exceeds the maximum', function () {
    $parser = new FrameParser(maxFrameBytes: 16);

    $frames = $parser->push('999999:v1:abc1234:[...]');

    expect($frames)->toBe([])
        ->and($parser->errored())->toBeTrue()
        ->and($parser->errorMessage())->toContain('maximum');
});

it('buffers an incomplete frame without emitting or erroring', function () {
    $parser = new FrameParser;

    $frames = $parser->push('20:v1:abc1234:[{"a"'); // fewer bytes than declared

    expect($frames)->toBe([])
        ->and($parser->errored())->toBeFalse();
});

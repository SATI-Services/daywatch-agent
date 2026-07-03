<?php

declare(strict_types=1);

use Daywatch\Agent\Ingest\Payload;

it('derives the 7-char token hash from xxh128 of the token', function () {
    expect(Payload::tokenHash('dw_secret'))
        ->toBe(substr(hash('xxh128', 'dw_secret'), 0, 7))
        ->and(strlen(Payload::tokenHash('dw_secret')))->toBe(7);
});

it('treats a null token as the empty string', function () {
    expect(Payload::tokenHash(null))->toBe(substr(hash('xxh128', ''), 0, 7));
});

it('frames a payload as {len}:v1:{hash}:{payload} with a byte-accurate length', function () {
    $hash = Payload::tokenHash('dw_secret');
    $body = '[{"t":"query"}]';

    $frame = Payload::frame($body, $hash);
    $rest = 'v1:'.$hash.':'.$body;

    expect($frame)->toBe(strlen($rest).':'.$rest)
        ->and($frame)->toStartWith(strlen($rest).':v1:'.$hash.':');
});

it('computes the frame length as bytes after the first colon, for any version', function () {
    $frame = Payload::frame('PING', 'abc1234', 'v2');
    [$len, $rest] = explode(':', $frame, 2);

    expect((int) $len)->toBe(strlen($rest))
        ->and($rest)->toBe('v2:abc1234:PING');
});

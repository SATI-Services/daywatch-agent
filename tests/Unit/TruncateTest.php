<?php

declare(strict_types=1);

use Daywatch\Agent\Support\Truncate;

it('leaves strings within the cap untouched', function () {
    expect(Truncate::tiny('hello'))->toBe('hello')
        ->and(Truncate::text('hello'))->toBe('hello')
        ->and(Truncate::medium('hello'))->toBe('hello');
});

it('truncates by BYTES at each tier', function () {
    expect(strlen(Truncate::tiny(str_repeat('a', 300))))->toBe(255)
        ->and(strlen(Truncate::text(str_repeat('a', 70_000))))->toBe(65_535)
        ->and(Truncate::tiny(str_repeat('a', 255)))->toBe(str_repeat('a', 255));
});

it('caps multibyte content at the byte limit (never char count)', function () {
    // "é" is 2 bytes in UTF-8; 200 of them = 400 bytes > 255 cap.
    $value = str_repeat('é', 200);

    $result = Truncate::tiny($value);

    expect(strlen($result))->toBeLessThanOrEqual(255)
        ->and(strlen($result))->toBeGreaterThan(250);
});

it('coerces null to an empty string', function () {
    expect(Truncate::tiny(null))->toBe('')
        ->and(Truncate::text(null))->toBe('');
});

it('exposes the documented cap tiers', function () {
    expect(Truncate::TINY)->toBe(255)
        ->and(Truncate::TEXT)->toBe(65_535)
        ->and(Truncate::MEDIUM)->toBe(16_777_215);
});

<?php

declare(strict_types=1);

use Daywatch\Agent\Records\Counters;

it('defines exactly the twelve child-event counters in canonical order', function () {
    expect(Counters::KEYS)->toBe([
        'exceptions', 'logs', 'queries', 'lazy_loads', 'jobs_queued', 'mail',
        'notifications', 'outgoing_requests', 'files_read', 'files_written',
        'cache_events', 'hydrated_models',
    ])->and(Counters::KEYS)->toHaveCount(12);
});

it('zeroes every counter', function () {
    $zeroed = Counters::zeroed();

    expect($zeroed)->toHaveCount(12)
        ->and(array_sum($zeroed))->toBe(0)
        ->and(array_keys($zeroed))->toBe(Counters::KEYS);
});

it('normalizes an arbitrary map to the canonical keys, zero-filled and int-coerced', function () {
    $normalized = Counters::normalize(['queries' => '5', 'unknown' => 9]);

    expect($normalized)->toHaveCount(12)
        ->and(array_keys($normalized))->toBe(Counters::KEYS)
        ->and($normalized['queries'])->toBe(5)
        ->and($normalized['exceptions'])->toBe(0)
        ->and($normalized)->not->toHaveKey('unknown');
});

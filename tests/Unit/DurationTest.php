<?php

declare(strict_types=1);

use Daywatch\Agent\Support\Duration;

it('converts a seconds delta to integer microseconds', function () {
    expect(Duration::us(0.001))->toBe(1000)
        ->and(Duration::us(1.5))->toBe(1_500_000)
        ->and(Duration::us(0.0000005))->toBe(1); // rounds
});

it('never returns a negative duration', function () {
    expect(Duration::us(-5.0))->toBe(0)
        ->and(Duration::us(0.0))->toBe(0);
});

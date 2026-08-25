<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests\Support;

use Daywatch\Agent\Support\Clock;

/**
 * Test double: a clock whose value only moves when told to.
 */
final class FrozenClock implements Clock
{
    public function __construct(private float $now = 0.0) {}

    public function microtime(): float
    {
        return $this->now;
    }

    public function set(float $now): void
    {
        $this->now = $now;
    }

    /** Advance the clock by $seconds and return the new value. */
    public function advance(float $seconds): float
    {
        return $this->now += $seconds;
    }
}

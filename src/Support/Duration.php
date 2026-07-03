<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

/**
 * Durations travel on the wire as integer microseconds (agent-faithful, no
 * float drift). This is the single place seconds are converted.
 */
final class Duration
{
    /** Convert a seconds delta to integer microseconds (never negative). */
    public static function us(float $seconds): int
    {
        if ($seconds <= 0.0) {
            return 0;
        }

        return (int) round($seconds * 1_000_000);
    }
}

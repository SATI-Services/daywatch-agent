<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

/**
 * Time source. Injected everywhere durations/timestamps are computed so tests
 * can freeze the clock and assert exact integer-microsecond durations.
 */
interface Clock
{
    /** Current wall-clock time in Unix seconds with microsecond precision. */
    public function microtime(): float;
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

final class SystemClock implements Clock
{
    public function microtime(): float
    {
        return microtime(true);
    }
}

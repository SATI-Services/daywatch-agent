<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon\Contracts;

use Daywatch\Agent\Daemon\LoopScheduler;

/**
 * Deferred-callback scheduler. Production wraps the ReactPHP loop's timers
 * ({@see LoopScheduler}); tests use a fake that captures
 * pending callbacks and fires them on demand, so retry-ladder / pause timing is
 * asserted deterministically with NO real sleeps.
 */
interface Scheduler
{
    /** Invoke $callback once, $seconds from now. */
    public function after(float $seconds, callable $callback): void;
}

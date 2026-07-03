<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Daemon\Contracts\Scheduler;
use React\EventLoop\LoopInterface;

/** Production {@see Scheduler} backed by the ReactPHP event loop's timers. */
final class LoopScheduler implements Scheduler
{
    public function __construct(private readonly LoopInterface $loop) {}

    public function after(float $seconds, callable $callback): void
    {
        $this->loop->addTimer($seconds, static function () use ($callback): void {
            $callback();
        });
    }
}

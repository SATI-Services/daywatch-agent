<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests\Support;

use Daywatch\Agent\Daemon\Contracts\Scheduler;

/**
 * Deterministic {@see Scheduler} test double. Captures deferred callbacks instead
 * of arming real timers so retry-ladder / pause timing is asserted with NO sleeps.
 */
final class FakeScheduler implements Scheduler
{
    /** @var list<array{seconds: float, callback: callable}> */
    public array $scheduled = [];

    public function after(float $seconds, callable $callback): void
    {
        $this->scheduled[] = ['seconds' => $seconds, 'callback' => $callback];
    }

    public function pending(): int
    {
        return count($this->scheduled);
    }

    /** @return list<float> */
    public function delays(): array
    {
        return array_map(static fn (array $s): float => $s['seconds'], $this->scheduled);
    }

    /** Fire the oldest pending callback (FIFO). */
    public function fireNext(): void
    {
        $item = array_shift($this->scheduled);

        if ($item !== null) {
            ($item['callback'])();
        }
    }

    /** Drain and fire all currently-pending callbacks (snapshot; bounded). */
    public function fireAll(int $max = 1000): void
    {
        $count = 0;

        while ($this->scheduled !== [] && $count++ < $max) {
            $this->fireNext();
        }
    }
}

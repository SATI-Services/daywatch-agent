<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Daemon\Contracts\Scheduler;
use Throwable;

/**
 * Emits the daemon's one-line stats log every `daemon.stats_interval` seconds
 * (services/daywatch-mcp/docs/agent-protocol.md §5) so an operator watching the supervisor's stdout can
 * see the daemon is alive and moving records. Self-reschedules through the
 * injected {@see Scheduler} (the ReactPHP loop in production, a fake in tests).
 *
 * CARDINAL RULE: emitting and rescheduling are both guarded — a logging failure
 * can never crash the event loop; worst case the periodic line simply stops.
 */
final class StatsReporter
{
    /** @var callable(string): void */
    private $logger;

    public function __construct(
        private readonly DaemonStats $stats,
        private readonly Scheduler $scheduler,
        callable $logger,
        private readonly int $intervalSeconds,
    ) {
        $this->logger = $logger;
    }

    /** Arm the periodic log. No-op when the interval is 0 (disabled) or negative. */
    public function start(): void
    {
        if ($this->intervalSeconds <= 0) {
            return;
        }

        $this->scheduleNext();
    }

    /** Emit one stats line right now. Guarded — nothing escapes. */
    public function emit(): void
    {
        try {
            ($this->logger)($this->stats->toLogLine());
        } catch (Throwable) {
        }
    }

    private function scheduleNext(): void
    {
        try {
            $this->scheduler->after((float) $this->intervalSeconds, function (): void {
                $this->emit();
                $this->scheduleNext();
            });
        } catch (Throwable) {
        }
    }
}

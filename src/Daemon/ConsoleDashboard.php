<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Daemon\Contracts\Scheduler;
use Daywatch\Agent\Support\Clock;
use Throwable;

/**
 * The live, in-place console for `daywatch:agent` when it runs attached to a TTY.
 * Every `refreshSeconds` it repaints a compact panel — the startup auth check,
 * daemon memory/uptime, ingest throughput, auth errors, and the last N log lines
 * ({@see RecentLog}) — redrawing over the previous frame with ANSI cursor control
 * instead of scrolling.
 *
 * Analogous to {@see StatsReporter}: it self-reschedules through the injected
 * {@see Scheduler} and every paint/reschedule is fully guarded. CARDINAL RULE:
 * a rendering or writer failure can never crash the event loop — worst case the
 * display simply freezes; the daemon keeps ingesting and forwarding.
 */
final class ConsoleDashboard
{
    /** @var callable(string): void */
    private $writer;

    /** @var callable(): array{0: int, 1: int} */
    private $memory;

    private readonly float $startedAt;

    /** Newlines painted last frame — how far up to move the cursor to overwrite. */
    private int $lastFrameLines = 0;

    /**
     * @param  callable(string): void  $writer  raw stdout writer (no trailing newline added)
     * @param  (callable(): array{0: int, 1: int})|null  $memory  [current, peak] bytes; defaults to the real process
     */
    public function __construct(
        private readonly DaemonStats $stats,
        private readonly RecentLog $recent,
        private readonly Clock $clock,
        private readonly Scheduler $scheduler,
        callable $writer,
        private readonly string $listen,
        private readonly int $refreshSeconds = 3,
        ?callable $memory = null,
    ) {
        $this->writer = $writer;
        $this->startedAt = $clock->microtime();
        $this->memory = $memory ?? static fn (): array => [memory_get_usage(true), memory_get_peak_usage(true)];
    }

    /** Draw the first frame immediately, then arm the periodic repaint. No-op if disabled. */
    public function start(): void
    {
        if ($this->refreshSeconds <= 0) {
            return;
        }

        $this->render();
        $this->scheduleNext();
    }

    /** Paint one frame right now. Guarded — nothing escapes. */
    public function render(): void
    {
        try {
            $this->paint($this->frame());
        } catch (Throwable) {
        }
    }

    private function scheduleNext(): void
    {
        try {
            $this->scheduler->after((float) $this->refreshSeconds, function (): void {
                $this->render();
                $this->scheduleNext();
            });
        } catch (Throwable) {
        }
    }

    /** Move to the top of the last frame, clear downward, and write the new one. */
    private function paint(string $frame): void
    {
        $out = '';

        if ($this->lastFrameLines > 0) {
            $out .= "\e[".$this->lastFrameLines.'A'; // cursor up to the first line
        }

        $out .= "\e[0J".$frame; // clear to end of screen, then repaint

        ($this->writer)($out);

        $this->lastFrameLines = substr_count($frame, "\n");
    }

    private function frame(): string
    {
        [$mem, $peak] = ($this->memory)();
        $s = $this->stats->toArray();

        $lines = [];
        $lines[] = 'Daywatch agent  ·  up '.$this->duration($this->clock->microtime() - $this->startedAt)
            .'  ·  listening '.$this->listen;
        $lines[] = 'target '.$s['base_url'];
        $lines[] = '';
        $lines[] = sprintf('  auth       %s', $this->stats->authSummary());
        $lines[] = sprintf('  memory     %-18s peak %s', $this->bytes((int) $mem), $this->bytes((int) $peak));
        $lines[] = sprintf('  received   %-18s buffered %s (%s)',
            number_format((int) $s['records_received']),
            number_format((int) $s['records_buffered']),
            $this->bytes((int) $s['buffered_bytes']),
        );
        $lines[] = sprintf('  sent       %-18s batches %s',
            number_format((int) $s['records_sent']),
            number_format((int) $s['batches_sent']),
        );
        $lines[] = sprintf('  failures   %-18s retries %s   auth errors %s',
            number_format((int) $s['send_failures']),
            number_format((int) $s['retries']),
            number_format($this->stats->authFailures()),
        );
        $lines[] = sprintf('  last flush %-18s (%s records)',
            $this->flushAge($s['last_flush_at']),
            number_format((int) $s['last_flush_records']),
        );
        $lines[] = '';
        $lines[] = 'recent';

        $recent = $this->recent->lines();

        if ($recent === []) {
            $lines[] = '  (nothing yet)';
        } else {
            foreach ($recent as $line) {
                $lines[] = '  '.$line;
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function flushAge(?float $lastFlushAt): string
    {
        if ($lastFlushAt === null) {
            return 'never';
        }

        return max(0, (int) round($this->clock->microtime() - $lastFlushAt)).'s ago';
    }

    private function duration(float $seconds): string
    {
        $seconds = max(0, (int) $seconds);

        return sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    private function bytes(int $b): string
    {
        if ($b < 1024) {
            return $b.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $b / 1024;
        $i = 0;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return sprintf('%.1f %s', $value, $units[$i]);
    }
}

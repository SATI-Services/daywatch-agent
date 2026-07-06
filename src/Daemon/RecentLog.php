<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

/**
 * A bounded ring buffer of the daemon's most recent log lines. In dashboard mode
 * ({@see ConsoleDashboard}) the daemon's logger writes here instead of scrolling
 * stdout, so the live display can show only the last N lines (auth errors, retries,
 * pauses …) without the console growing unbounded.
 *
 * Plain string bookkeeping only — no I/O, nothing that can throw into the caller.
 */
final class RecentLog
{
    /** @var list<string> */
    private array $lines = [];

    private int $total = 0;

    public function __construct(private readonly int $capacity = 10) {}

    /** Record one line, evicting the oldest once capacity is exceeded. */
    public function push(string $line): void
    {
        $this->total++;
        $this->lines[] = $line;

        if (count($this->lines) > $this->capacity) {
            array_shift($this->lines);
        }
    }

    /** @return list<string> the retained tail, oldest first */
    public function lines(): array
    {
        return $this->lines;
    }

    /** Total lines ever pushed (including evicted ones). */
    public function total(): int
    {
        return $this->total;
    }
}

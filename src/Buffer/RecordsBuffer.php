<?php

declare(strict_types=1);

namespace Daywatch\Agent\Buffer;

use Daywatch\Agent\Core;

/**
 * In-process, bounded buffer of record arrays (daywatch-mcp/docs/agent-protocol.md §3).
 * Holds ≤ event_buffer records. Overflow policy (auto-digest vs ring-drop) is
 * decided by {@see Core} based on the head-sampling decision —
 * this class only stores, counts, and empties.
 */
class RecordsBuffer
{
    /** @var array<int, array<string, mixed>> */
    private array $records = [];

    public function __construct(private int $limit = 500) {}

    public function write(array $record): void
    {
        $this->records[] = $record;
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function isFull(): bool
    {
        return count($this->records) >= $this->limit;
    }

    /** Bound memory when digesting is disabled: drop oldest until within limit. */
    public function trimToLimit(): void
    {
        while (count($this->records) > $this->limit) {
            array_shift($this->records);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->records;
    }

    /**
     * Return and clear the buffered records (for transmission).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pull(): array
    {
        $records = $this->records;
        $this->records = [];

        return $records;
    }

    /** Discard the buffered records (unsampled execution). */
    public function flush(): void
    {
        $this->records = [];
    }
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Buffer;

use Daywatch\Agent\Core;

/**
 * In-process, bounded buffer of record arrays (daywatch-mcp/docs/agent-protocol.md §3).
 * Overflow policy (auto-digest vs ring-drop) is decided by {@see Core} from the
 * head-sampling decision — this class only stores, measures, and empties.
 *
 * TWO bounds, because either one alone leaves the host exposed: a record COUNT
 * (`event_buffer`, default 500) and an approximate BYTE size (`buffer_bytes`).
 * Field caps allow a single record to carry megabytes (a `sql` string may reach
 * 16 MB), so 500 of them is not a memory bound in any useful sense — the byte
 * bound is what actually keeps the host's footprint predictable.
 */
class RecordsBuffer
{
    /** @var array<int, array<string, mixed>> */
    private array $records = [];

    /** @var array<int, int> per-record estimated sizes, index-aligned with */
    private array $sizes = [];

    private int $bytes = 0;

    /**
     * @param  int  $byteLimit  Approximate byte ceiling for the whole buffer
     *                          (0 disables the byte bound and leaves only the record count).
     */
    public function __construct(private int $limit = 500, private int $byteLimit = 5_000_000) {}

    public function write(array $record): void
    {
        $size = self::sizeOf($record);

        $this->records[] = $record;
        $this->sizes[] = $size;
        $this->bytes += $size;
    }

    public function count(): int
    {
        return count($this->records);
    }

    /** Approximate bytes currently held (the byte-bound's measure). */
    public function bytes(): int
    {
        return $this->bytes;
    }

    public function isFull(): bool
    {
        return count($this->records) >= $this->limit
            || ($this->byteLimit > 0 && $this->bytes >= $this->byteLimit);
    }

    /**
     * Bound memory when digesting is disabled: ring-drop the oldest records until
     * both bounds are satisfied. A single record larger than the byte limit is
     * kept (dropping it would leave the buffer empty and the loop spinning) —
     * the count bound still applies.
     */
    public function trimToLimit(): void
    {
        while ($this->records !== [] && $this->overCapacity()) {
            array_shift($this->records);
            $this->bytes -= (int) array_shift($this->sizes);
        }

        if ($this->records === []) {
            $this->bytes = 0;
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

        $this->flush();

        return $records;
    }

    /** Discard the buffered records (unsampled execution). */
    public function flush(): void
    {
        $this->records = [];
        $this->sizes = [];
        $this->bytes = 0;
    }

    /** Over either bound, with more than one record left to give up. */
    private function overCapacity(): bool
    {
        if (count($this->records) > $this->limit) {
            return true;
        }

        return $this->byteLimit > 0
            && $this->bytes > $this->byteLimit
            && count($this->records) > 1;
    }

    /**
     * Estimated encoded size of one record. Deliberately an estimate computed by
     * one shallow walk (no json_encode — that would double the cost of the hot
     * path): string lengths plus a fixed allowance per key for scalars and
     * punctuation. It only has to track magnitude for the bound to work.
     */
    private static function sizeOf(array $record): int
    {
        $bytes = 0;

        foreach ($record as $key => $value) {
            $bytes += strlen((string) $key) + 8;

            if (is_string($value)) {
                $bytes += strlen($value);
            } elseif (is_array($value)) {
                $bytes += self::sizeOf($value);
            }
        }

        return $bytes;
    }
}

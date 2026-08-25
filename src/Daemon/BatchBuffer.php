<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Support\Clock;

/**
 * String-level batch accumulator for the daemon (agent protocol §5).
 *
 * Incoming digests are JSON arrays `[{...},{...}]`; this buffer strips the outer
 * brackets and comma-joins the inner objects into one growing string — the record
 * JSON is NEVER re-parsed (O(bytes) memcpy, no decode cost at 6 MB scale). A flush
 * wraps the accumulated objects back into `{"records":[ ... ]}`.
 *
 * Flush triggers: accumulated size ≥ flush_bytes (default 6 MB), or flush_interval
 * seconds (default 10) since the first byte was buffered.
 */
final class BatchBuffer
{
    private string $records = '';

    private int $size = 0;

    private float $firstByteAt = 0.0;

    public function __construct(
        private readonly Clock $clock,
        private readonly int $flushBytes = 6_000_000,
        private readonly int $flushIntervalSeconds = 10,
    ) {}

    /**
     * Append one digest payload (a `[...]` JSON array string). Returns the number
     * of record bytes added. Malformed (non-array) payloads are ignored.
     */
    public function add(string $payload): int
    {
        $inner = $this->stripBrackets($payload);

        if ($inner === '') {
            return 0;
        }

        if ($this->records === '') {
            $this->records = $inner;
            $this->firstByteAt = $this->clock->microtime();
        } else {
            $this->records .= ','.$inner;
        }

        $this->size = strlen($this->records);

        return strlen($inner);
    }

    public function isEmpty(): bool
    {
        return $this->records === '';
    }

    public function size(): int
    {
        return $this->size;
    }

    /** Would appending this many record bytes push the buffer past the flush ceiling? */
    public function wouldExceed(int $incomingBytes): bool
    {
        return ! $this->isEmpty() && ($this->size + $incomingBytes) >= $this->flushBytes;
    }

    /** Is a flush due right now (size ceiling or age)? */
    public function shouldFlush(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        if ($this->size >= $this->flushBytes) {
            return true;
        }

        return ($this->clock->microtime() - $this->firstByteAt) >= $this->flushIntervalSeconds;
    }

    /**
     * Drain the buffer into a ready-to-POST `{"records":[ ... ]}` body, resetting
     * internal state. Returns null when the buffer is empty.
     */
    public function flush(): ?string
    {
        if ($this->records === '') {
            return null;
        }

        $body = '{"records":['.$this->records.']}';

        $this->records = '';
        $this->size = 0;
        $this->firstByteAt = 0.0;

        return $body;
    }

    /** Peek the number of record bytes in a `[...]` payload without buffering it. */
    public function measure(string $payload): int
    {
        return strlen($this->stripBrackets($payload));
    }

    private function stripBrackets(string $payload): string
    {
        $payload = trim($payload);

        $length = strlen($payload);

        if ($length < 2 || $payload[0] !== '[' || $payload[$length - 1] !== ']') {
            return '';
        }

        return trim(substr($payload, 1, $length - 2));
    }
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Daemon\Contracts\RecordSink;
use Throwable;

/**
 * Shared daemon coordinator: owns the string-level {@see BatchBuffer} and the
 * {@see IngestDispatcher}. Connections feed record payloads in via {@see ingest()};
 * a periodic {@see tick()} drains the age-based (10 s) flush. Pre-flushes when an
 * incoming payload would push the buffer past the size ceiling (docs §5).
 */
final class IngestServer implements RecordSink
{
    public function __construct(
        private readonly BatchBuffer $buffer,
        private readonly IngestDispatcher $dispatcher,
        private readonly ?DaemonStats $stats = null,
    ) {}

    /** Accept one digest payload (a `[...]` JSON array string). */
    public function ingest(string $payload): void
    {
        try {
            $incoming = $this->buffer->measure($payload);

            if ($incoming === 0) {
                return;
            }

            // Counted ONCE, at the frame boundary — a string scan, never a JSON decode.
            $records = $this->stats !== null ? RecordCounter::count($payload) : 0;

            // Pre-flush so a large incoming digest doesn't blow past the ceiling.
            if ($this->buffer->wouldExceed($incoming)) {
                $this->flushNow();
            }

            $this->buffer->add($payload);
            $this->stats?->received($records, $this->buffer->size());

            if ($this->buffer->shouldFlush()) {
                $this->flushNow();
            }
        } catch (Throwable) {
        }
    }

    /** Periodic drain — flushes when the age (or size) trigger is due. */
    public function tick(): void
    {
        try {
            if ($this->buffer->shouldFlush()) {
                $this->flushNow();
            }
        } catch (Throwable) {
        }
    }

    /** Flush everything on shutdown / unknown-version graceful exit. */
    public function finalDigest(): void
    {
        $this->flushNow();
    }

    private function flushNow(): void
    {
        try {
            $body = $this->buffer->flush();

            if ($body !== null) {
                $records = $this->stats?->flushed() ?? 0;
                $this->dispatcher->dispatch($body, $records);
            }
        } catch (Throwable) {
        }
    }
}

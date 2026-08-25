<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Support\Clock;

/**
 * O(1) ingest bookkeeping for the daywatch:agent daemon (daywatch-mcp/docs/agent-protocol.md
 * §4 STATS reply / §5 stats log line). Every mutator is a plain integer bump on a
 * path the daemon already executes — record JSON is never re-parsed to maintain
 * these: records are counted once per digest at the frame boundary
 * ({@see RecordCounter}) and the counts ride alongside the {@see BatchBuffer}
 * byte counts from there on.
 *
 * `base_url` is reported verbatim because the daemon cannot know which ingest
 * implementation (Laravel app or Java relay) serves that URL.
 */
final class DaemonStats
{
    private int $recordsReceived = 0;

    private int $recordsBuffered = 0;

    private int $bufferedBytes = 0;

    private int $batchesSent = 0;

    private int $recordsSent = 0;

    private int $sendFailures = 0;

    private int $retries = 0;

    private int $authFailures = 0;

    /** Startup auth-check state — {@see AuthProbeResult} constants. */
    private string $authState = AuthProbeResult::PENDING;

    private string $authSummary = 'checking…';

    private ?float $lastFlushAt = null;

    private int $lastFlushRecords = 0;

    public function __construct(
        private readonly string $baseUrl,
        private readonly Clock $clock,
    ) {}

    /** A digest was buffered: $records more records held; the buffer now holds $bufferedBytes. */
    public function received(int $records, int $bufferedBytes): void
    {
        $this->recordsReceived += $records;
        $this->recordsBuffered += $records;
        $this->bufferedBytes = $bufferedBytes;
    }

    /** The batch buffer was drained. Returns the record count that flushed. */
    public function flushed(): int
    {
        $records = $this->recordsBuffered;

        $this->lastFlushAt = $this->clock->microtime();
        $this->lastFlushRecords = $records;
        $this->recordsBuffered = 0;
        $this->bufferedBytes = 0;

        return $records;
    }

    /** A batch POST got a 2xx. */
    public function sent(int $records): void
    {
        $this->batchesSent++;
        $this->recordsSent += $records;
    }

    /** A batch POST attempt failed (network error or non-2xx status). */
    public function failed(): void
    {
        $this->sendFailures++;
    }

    /** A batch was scheduled for retry (backoff ladder or 429). */
    public function retried(): void
    {
        $this->retries++;
    }

    /** A batch POST was rejected with 401 — a bad/expired token. */
    public function authFailed(): void
    {
        $this->authFailures++;
    }

    /** Record the startup authentication check's outcome ({@see AuthProbe}). */
    public function authProbed(string $state, string $summary): void
    {
        $this->authState = $state;
        $this->authSummary = $summary;
    }

    /**
     * Startup auth-check state / one-line summary. Surfaced by the live
     * {@see ConsoleDashboard} and the boot log; kept out of {@see toArray()}
     * alongside {@see authFailures()} — that array is the frozen STATS wire
     * contract (daywatch-mcp/docs/agent-protocol.md §4).
     */
    public function authState(): string
    {
        return $this->authState;
    }

    public function authSummary(): string
    {
        return $this->authSummary;
    }

    /**
     * Count of 401 rejections. Surfaced by the live {@see ConsoleDashboard}; kept
     * out of {@see toArray()} deliberately — that array is the frozen STATS wire
     * contract (daywatch-mcp/docs/agent-protocol.md §4).
     */
    public function authFailures(): int
    {
        return $this->authFailures;
    }

    /**
     * The STATS reply payload — field names are contract (daywatch-mcp/docs/agent-protocol.md §4).
     *
     * @return array<string, string|int|float|null>
     */
    public function toArray(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'records_received' => $this->recordsReceived,
            'records_buffered' => $this->recordsBuffered,
            'buffered_bytes' => $this->bufferedBytes,
            'batches_sent' => $this->batchesSent,
            'records_sent' => $this->recordsSent,
            'send_failures' => $this->sendFailures,
            'retries' => $this->retries,
            'last_flush_at' => $this->lastFlushAt,
            'last_flush_records' => $this->lastFlushRecords,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /** One supervisor-visible stdout line (daywatch-mcp/docs/agent-protocol.md §5). */
    public function toLogLine(): string
    {
        $lastFlush = $this->lastFlushAt === null
            ? 'never'
            : max(0, (int) round($this->clock->microtime() - $this->lastFlushAt)).'s';

        return sprintf(
            '[daywatch:agent] stats target=%s received=%d buffered=%d buffered_bytes=%d batches=%d sent=%d failures=%d retries=%d last_flush=%s last_flush_records=%d',
            $this->baseUrl,
            $this->recordsReceived,
            $this->recordsBuffered,
            $this->bufferedBytes,
            $this->batchesSent,
            $this->recordsSent,
            $this->sendFailures,
            $this->retries,
            $lastFlush,
            $this->lastFlushRecords,
        );
    }
}

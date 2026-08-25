<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Daemon\Contracts\HttpSender;
use Daywatch\Agent\Daemon\Contracts\Scheduler;
use Daywatch\Agent\Support\Uuid;
use Throwable;

/**
 * Turns flushed batch bodies into gzipped POSTs to {base_url}/api/ingest and
 * applies the response contract (agent protocol §6): success discard,
 * 401/413/422 drop, 429 retry-after, 503 `{stop}` NullBuffer pause, and the
 * network/5xx retry ladder. Transport + timers are injected so all of this is
 * deterministically testable with no sockets and no sleeps.
 */
final class IngestDispatcher
{
    /** Retry ladder for network errors / 5xx without `stop` (seconds). */
    private const LADDER = [2.5, 5, 10, 15, 30, 60, 120, 240, 300];

    private const HOLD_SECONDS = 300.0;

    private const MAX_SECONDS = 3600.0;

    /** Default 503 pause window when the server omits refresh_in. */
    public const DEFAULT_REFRESH_IN = 900.0;

    private int $inFlight = 0;

    private bool $paused = false;

    private int $consecutiveFailures = 0;

    /** @var array<int, Batch> retained batches awaiting retry (FIFO for eviction) */
    private array $retained = [];

    private int $retainedBytes = 0;

    private int $nextRetainId = 0;

    /** @var callable(string): void */
    private $logger;

    /** @var callable(): string */
    private $batchIdFactory;

    public function __construct(
        private readonly HttpSender $sender,
        private readonly Scheduler $scheduler,
        private readonly string $url,
        private readonly string $token,
        private readonly string $server,
        private readonly string $userAgent = 'DaywatchAgent/dev',
        private readonly int $maxConcurrent = 5,
        private readonly int $retainCapBytes = 33_554_432,
        ?callable $logger = null,
        ?callable $batchIdFactory = null,
        private readonly ?DaemonStats $stats = null,
    ) {
        $this->logger = $logger ?? static fn (string $m) => null;
        $this->batchIdFactory = $batchIdFactory ?? static fn (): string => Uuid::v4();
    }

    /** Ingest one flushed `{"records":[...]}` body: gzip, then attempt to POST. */
    public function dispatch(string $body, int $records = 0): void
    {
        try {
            if ($this->paused) {
                $this->log('paused (over quota) — dropping batch');

                return;
            }

            // ext-zlib is a soft suggestion, not a hard require: if it is absent
            // gzencode() is undefined and calling it would fatal. Guard so a
            // missing extension pauses upload (drop + log) rather than crashing.
            if (! function_exists('gzencode')) {
                $this->log('ext-zlib unavailable — dropping batch (install/enable zlib to enable ingest)');

                return;
            }

            $gzip = @gzencode($body, 6);

            if ($gzip === false) {
                $this->log('gzip failed — dropping batch');

                return;
            }

            $batch = new Batch(($this->batchIdFactory)(), $gzip, strlen($gzip), $records);

            $this->attemptSend($batch);
        } catch (Throwable $e) {
            $this->log('dispatch failed: '.$e->getMessage());
        }
    }

    public function inFlight(): int
    {
        return $this->inFlight;
    }

    public function isPaused(): bool
    {
        return $this->paused;
    }

    public function retainedBytes(): int
    {
        return $this->retainedBytes;
    }

    private function attemptSend(Batch $batch): void
    {
        if ($this->paused) {
            $this->log('paused (over quota) — dropping batch');

            return;
        }

        if ($this->inFlight >= $this->maxConcurrent) {
            $this->log('max concurrent in-flight ('.$this->maxConcurrent.') reached — dropping batch');

            return;
        }

        $this->inFlight++;

        $headers = [
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type' => 'application/json',
            'Content-Encoding' => 'gzip',
            'Accept' => 'application/json',
            'Daywatch-Server' => $this->server,
            'Daywatch-Batch-Id' => $batch->id,
            'User-Agent' => $this->userAgent,
        ];

        $this->sender->send('POST', $this->url, $headers, $batch->gzipBody)->then(
            function (HttpResponse $response) use ($batch): void {
                $this->inFlight = max(0, $this->inFlight - 1);
                $this->handleResponse($batch, $response);
            },
            function (Throwable $e) use ($batch): void {
                $this->inFlight = max(0, $this->inFlight - 1);
                $this->stats?->failed();
                $this->log('network error: '.$e->getMessage());
                $this->retryOnLadder($batch);
            },
        );
    }

    private function handleResponse(Batch $batch, HttpResponse $response): void
    {
        $status = $response->status;

        if ($status >= 200 && $status < 300) {
            $this->consecutiveFailures = 0;
            $this->paused = false;
            $this->stats?->sent($batch->records);

            return;
        }

        $this->stats?->failed();

        if ($status === 401) {
            $this->stats?->authFailed();
            $this->log('401 unauthorized — bad token, dropping batch');

            return;
        }

        if ($status === 413 || $status === 422) {
            $this->log($status.' — poison batch dropped');

            return;
        }

        if ($status === 429) {
            $retryIn = (float) ($response->json()['retry_in'] ?? self::LADDER[0]);
            $this->log('429 rate limited — retrying in '.$retryIn.'s');
            $this->retainAndSchedule($batch, max(0.0, $retryIn));

            return;
        }

        if ($status === 503 && ($response->json()['stop'] ?? false) === true) {
            $refreshIn = (float) ($response->json()['refresh_in'] ?? self::DEFAULT_REFRESH_IN);
            $this->pause($refreshIn);

            return;
        }

        if ($status >= 500) {
            $this->log($status.' server error — retrying on ladder');
            $this->retryOnLadder($batch);

            return;
        }

        // Any other 4xx: unexpected but non-retryable — drop.
        $this->log($status.' unexpected — dropping batch');
    }

    /** Enter the over-quota pause: drop all telemetry, re-probe after refresh_in. */
    private function pause(float $refreshIn): void
    {
        $this->paused = true;
        $this->log('503 stop — pausing telemetry for '.$refreshIn.'s (NullBuffer)');

        $this->scheduler->after($refreshIn, function (): void {
            // Resume attempting; a subsequent 2xx confirms, another 503 re-pauses.
            $this->paused = false;
            $this->log('pause window elapsed — resuming');
        });
    }

    private function retryOnLadder(Batch $batch): void
    {
        $this->consecutiveFailures++;
        $this->retainAndSchedule($batch, $this->delayFor($this->consecutiveFailures));
    }

    private function retainAndSchedule(Batch $batch, float $delay): void
    {
        $batch->attempts++;

        $id = $this->nextRetainId++;
        $this->retained[$id] = $batch;
        $this->retainedBytes += $batch->size;

        // Bound retry memory: evict oldest beyond the cap (telemetry loss over OOM).
        while ($this->retainedBytes > $this->retainCapBytes && count($this->retained) > 1) {
            $oldest = array_key_first($this->retained);
            $this->retainedBytes -= $this->retained[$oldest]->size;
            unset($this->retained[$oldest]);
            $this->log('retry buffer over '.$this->retainCapBytes.'B — dropped oldest retained batch');
        }

        if ($this->retainedBytes > $this->retainCapBytes) {
            // A single batch exceeds the cap on its own — drop it.
            $this->retainedBytes -= $batch->size;
            unset($this->retained[$id]);
            $this->log('single batch exceeds retry buffer cap — dropped');

            return;
        }

        $this->stats?->retried();

        $this->scheduler->after($delay, function () use ($id): void {
            if (! isset($this->retained[$id])) {
                return; // evicted while waiting
            }

            $batch = $this->retained[$id];
            $this->retainedBytes -= $batch->size;
            unset($this->retained[$id]);

            $this->attemptSend($batch);
        });
    }

    private function delayFor(int $failures): float
    {
        if ($failures <= count(self::LADDER)) {
            return self::LADDER[$failures - 1];
        }

        if ($failures <= 12) {
            return self::HOLD_SECONDS;
        }

        return self::MAX_SECONDS;
    }

    private function log(string $message): void
    {
        try {
            ($this->logger)('[daywatch:agent] '.$message);
        } catch (Throwable) {
        }
    }
}

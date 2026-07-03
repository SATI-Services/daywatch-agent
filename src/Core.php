<?php

declare(strict_types=1);

namespace Daywatch\Agent;

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Ingest\Client;
use Daywatch\Agent\Records\Counters;
use Daywatch\Agent\Support\Clock;
use Daywatch\Agent\Support\ContextPropagation;
use Daywatch\Agent\Support\Duration;
use Daywatch\Agent\Support\ExecutionStage;
use Daywatch\Agent\Support\Uuid;
use Throwable;

/**
 * Per-execution state + the digest/flush decision engine
 * (docs/agent-protocol.md §3). One instance per app process; its mutable state
 * is RESET between executions so worker/Octane loops never bleed telemetry.
 *
 * CARDINAL RULE: every public entry point here is reached from a sensor hook and
 * must be un-crashable. Recording and transmission are internally guarded; the
 * host app never sees a throw.
 */
class Core
{
    private const NEXT_STAGE = [
        ExecutionStage::BOOTSTRAP => ExecutionStage::BEFORE_MIDDLEWARE,
        ExecutionStage::BEFORE_MIDDLEWARE => ExecutionStage::ACTION,
        ExecutionStage::ACTION => ExecutionStage::RENDER,
        ExecutionStage::RENDER => ExecutionStage::AFTER_MIDDLEWARE,
        ExecutionStage::AFTER_MIDDLEWARE => ExecutionStage::SENDING,
        ExecutionStage::SENDING => ExecutionStage::TERMINATING,
        ExecutionStage::TERMINATING => ExecutionStage::END,
    ];

    public string $traceId;

    public string $executionId;

    public string $executionSource = 'request';

    public string $executionPreview = '';

    public string $executionStage = ExecutionStage::BOOTSTRAP;

    /** @var array<string, int> */
    private array $counters;

    /** @var array<string, int> */
    private array $stages;

    private float $stageStartedAt = 0.0;

    private float $requestStartedAt = 0.0;

    private bool $sampleDecision = false;

    private ?bool $sampleOverride = null;

    private bool $paused = false;

    private string $exceptionPreview = '';

    private string|int|null $userId = null;

    /** @var (callable(mixed): (string|int|null))|null */
    private $userResolver = null;

    /** @var array<int, string> classes/objects to suppress from exception recording */
    private array $ignored = [];

    public function __construct(
        private RecordsBuffer $buffer,
        private Client $client,
        private Clock $clock,
        private bool $enabled = true,
        private string $deploy = '',
        private string $server = '',
        private float $requestSampleRate = 1.0,
        private float $commandSampleRate = 1.0,
        private float $exceptionSampleRate = 1.0,
        private bool $captureExceptionSourceCode = true,
    ) {
        $this->traceId = Uuid::v4();
        $this->executionId = $this->traceId;
        $this->counters = Counters::zeroed();
        $this->stages = array_fill_keys(ExecutionStage::REQUEST_STAGES, 0);
    }

    // ── configuration accessors ───────────────────────────────────────────

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function clock(): Clock
    {
        return $this->clock;
    }

    public function deploy(): string
    {
        return $this->deploy;
    }

    public function server(): string
    {
        return $this->server;
    }

    public function captureExceptionSourceCode(): bool
    {
        return $this->captureExceptionSourceCode;
    }

    // ── execution lifecycle ───────────────────────────────────────────────

    /**
     * Begin a request execution: fresh (or propagated) ids, a single head-sampling
     * decision, and the bootstrap stage clock. Idempotent within one request.
     */
    public function prepareForRequest(?float $startedAt = null): void
    {
        try {
            $this->reset();

            $this->executionSource = 'request';
            $this->requestStartedAt = $startedAt ?? $this->clock->microtime();
            $this->stageStartedAt = $this->requestStartedAt;
            $this->executionStage = ExecutionStage::BOOTSTRAP;

            $this->adoptPropagatedTrace();

            // The request is the execution root: execution_id == trace_id.
            $this->executionId = $this->traceId;

            $this->sampleDecision = $this->decideSampling($this->requestSampleRate);

            $this->propagate();
        } catch (Throwable) {
            // never break the request
        }
    }

    /**
     * Begin a job execution (queue hop). Adopts the propagated trace/sampling/user
     * from the dispatching execution so sub-records (queries, exceptions) recorded
     * during the job continue the trace. Full job-attempt records land in M4.
     */
    public function prepareForJob(): void
    {
        try {
            $this->reset();

            $this->executionSource = 'job';
            // The job attempt is its own execution under the inherited trace.
            $this->executionId = Uuid::v4();
            $this->executionStage = ExecutionStage::ACTION;
            $this->requestStartedAt = $this->clock->microtime();
            $this->stageStartedAt = $this->requestStartedAt;

            $this->adoptPropagatedTrace();

            $this->propagate();
        } catch (Throwable) {
        }
    }

    /**
     * Reset all mutable per-execution state (worker/Octane boundary). Fresh ids
     * and counters, peak-memory baseline reset, buffer discarded, sampling forced
     * off so worker-loop noise is never transmitted until a real execution starts.
     */
    public function reset(): void
    {
        try {
            $this->traceId = Uuid::v4();
            $this->executionId = $this->traceId;
            $this->executionSource = 'request';
            $this->executionPreview = '';
            $this->executionStage = ExecutionStage::BOOTSTRAP;
            $this->counters = Counters::zeroed();
            $this->stages = array_fill_keys(ExecutionStage::REQUEST_STAGES, 0);
            $this->stageStartedAt = 0.0;
            $this->requestStartedAt = 0.0;
            $this->sampleDecision = false;
            $this->sampleOverride = null;
            $this->paused = false;
            $this->exceptionPreview = '';
            $this->userId = null;

            $this->buffer->flush();

            if (function_exists('memory_reset_peak_usage')) {
                memory_reset_peak_usage();
            }
        } catch (Throwable) {
        }
    }

    /** Close the given stage and advance the running stage to the next one. */
    public function beginStage(string $closingStage): void
    {
        try {
            $now = $this->clock->microtime();

            if (array_key_exists($closingStage, $this->stages)) {
                $this->stages[$closingStage] += Duration::us($now - $this->stageStartedAt);
            }

            $this->stageStartedAt = $now;
            $this->executionStage = self::NEXT_STAGE[$closingStage] ?? ExecutionStage::END;
        } catch (Throwable) {
        }
    }

    public function setExecutionPreview(string $preview): void
    {
        $this->executionPreview = $preview;
    }

    // ── sampling ──────────────────────────────────────────────────────────

    public function shouldSample(): bool
    {
        return $this->sampleOverride ?? $this->sampleDecision;
    }

    public function sample(): void
    {
        $this->sampleOverride = true;
        $this->propagate();
    }

    public function dontSample(): void
    {
        $this->sampleOverride = false;
        $this->propagate();
    }

    private function decideSampling(float $rate): bool
    {
        if ($rate >= 1.0) {
            return true;
        }

        if ($rate <= 0.0) {
            return false;
        }

        try {
            return random_int(1, PHP_INT_MAX) <= (int) ($rate * PHP_INT_MAX);
        } catch (Throwable) {
            return false;
        }
    }

    // ── pause / resume ────────────────────────────────────────────────────

    public function pause(): void
    {
        $this->paused = true;
    }

    public function resume(): void
    {
        $this->paused = false;
    }

    public function isPaused(): bool
    {
        return $this->paused;
    }

    // ── user ──────────────────────────────────────────────────────────────

    public function user(string|int|callable|null $id): void
    {
        if (is_callable($id)) {
            $this->userResolver = $id;

            return;
        }

        $this->userId = $id;
        $this->propagate();
    }

    /** Resolve the user id string for records ('' when anonymous). */
    public function resolveUser(): string
    {
        try {
            if ($this->userId !== null && $this->userId !== '') {
                return (string) $this->userId;
            }

            $authUser = $this->authUser();

            if ($this->userResolver !== null) {
                $resolved = ($this->userResolver)($authUser);

                return $resolved === null ? '' : (string) $resolved;
            }

            if (is_object($authUser) && method_exists($authUser, 'getAuthIdentifier')) {
                $id = $authUser->getAuthIdentifier();

                return $id === null ? '' : (string) $id;
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function authUser(): mixed
    {
        try {
            if (function_exists('auth')) {
                return auth()->user();
            }
        } catch (Throwable) {
        }

        return null;
    }

    // ── recording ─────────────────────────────────────────────────────────

    public function increment(string $counter, int $by = 1): void
    {
        if (array_key_exists($counter, $this->counters)) {
            $this->counters[$counter] += $by;
        }
    }

    /** @return array<string, int> */
    public function counters(): array
    {
        return $this->counters;
    }

    /** @return array<string, int> */
    public function stages(): array
    {
        return $this->stages;
    }

    public function requestStartedAt(): float
    {
        return $this->requestStartedAt;
    }

    public function exceptionPreview(): string
    {
        return $this->exceptionPreview;
    }

    /** Write a fully-built record array into the buffer, honouring overflow policy. */
    public function write(array $record): void
    {
        try {
            if (! $this->enabled || $this->paused) {
                return;
            }

            $this->buffer->write($record);

            if ($this->buffer->isFull()) {
                if ($this->shouldSample()) {
                    $this->digest();
                } else {
                    // Digesting disabled for this (unsampled) execution: bound
                    // memory by ring-dropping the oldest records.
                    $this->buffer->trimToLimit();
                }
            }
        } catch (Throwable) {
        }
    }

    public function recordQuery(array $record): void
    {
        $this->increment('queries');
        $this->write($record);
    }

    /**
     * Record an exception and re-roll sampling at the exception rate so errors
     * escape sampled-out traces (docs/agent-protocol.md §3).
     */
    public function recordException(array $record, string $preview): void
    {
        $this->increment('exceptions');
        $this->exceptionPreview = $preview;

        if (! $this->shouldSample() && $this->decideSampling($this->exceptionSampleRate)) {
            $this->sampleOverride = true;
            $this->propagate();
        }

        $this->write($record);
    }

    public function markIgnored(Throwable $e): void
    {
        $this->ignored[] = spl_object_hash($e);
    }

    public function isIgnored(Throwable $e): bool
    {
        return in_array(spl_object_hash($e), $this->ignored, true);
    }

    // ── digest / flush ────────────────────────────────────────────────────

    /** End of execution: transmit if sampled, discard otherwise. */
    public function finishExecution(): void
    {
        try {
            if ($this->shouldSample()) {
                $this->digest();
            } else {
                $this->buffer->flush();
            }
        } catch (Throwable) {
        }
    }

    /** Transmit the buffered records to the local daemon immediately. */
    public function digest(): void
    {
        try {
            $records = $this->buffer->pull();

            if ($records === []) {
                return;
            }

            $payload = json_encode(
                $records,
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
            );

            if ($payload === false) {
                return;
            }

            $this->client->send($payload);
        } catch (Throwable) {
        }
    }

    // ── propagation ───────────────────────────────────────────────────────

    private function propagate(): void
    {
        ContextPropagation::write($this->traceId, $this->shouldSample(), $this->resolveUser());
    }

    /** Adopt a trace propagated from an upstream execution (queue hop), if any. */
    private function adoptPropagatedTrace(): void
    {
        $propagated = ContextPropagation::read();

        if ($propagated['trace_id'] !== null) {
            $this->traceId = $propagated['trace_id'];
        }

        if ($propagated['should_sample'] !== null) {
            $this->sampleOverride = $propagated['should_sample'];
        }

        if ($propagated['user_id'] !== null && $propagated['user_id'] !== '') {
            $this->userId = $propagated['user_id'];
        }
    }
}

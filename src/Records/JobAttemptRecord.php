<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `job-attempt` record (daywatch-mcp/docs/agent-protocol.md §2) — one execution of a
 * queued job in a worker. The attempt IS its own execution (`attempt_id` is the
 * execution id) under the inherited `trace_id`, so it carries no `execution_*`
 * duplication. Carries the full counters block. `_group = xxh128(name)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class JobAttemptRecord
{
    /**
     * @param  array<string, int>  $counters  keyed by Counters::KEYS
     */
    public function __construct(
        public float $timestamp,
        public string $deploy,
        public string $server,
        public string $traceId,
        public string $user,
        public string $jobId,
        public string $attemptId,
        public int $attempt,
        public string $name,
        public string $connection,
        public string $queue,
        public string $status,
        public int $duration,
        public array $counters,
        public int $peakMemoryUsage,
        public string $exceptionPreview,
        public string $context,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'job-attempt',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::name($this->name),
            'trace_id' => $this->traceId,
            'user' => Truncate::tiny($this->user),
            'job_id' => $this->jobId,
            'attempt_id' => $this->attemptId,
            'attempt' => $this->attempt,
            'name' => Truncate::tiny($this->name),
            'connection' => Truncate::tiny($this->connection),
            'queue' => Truncate::tiny($this->queue),
            'status' => $this->status,
            'duration' => $this->duration,
            ...Counters::normalize($this->counters),
            'peak_memory_usage' => $this->peakMemoryUsage,
            'exception_preview' => Truncate::tiny($this->exceptionPreview),
            'context' => Truncate::text($this->context),
        ];
    }
}

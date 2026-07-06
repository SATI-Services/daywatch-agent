<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `queued-job` record (daywatch-mcp/docs/agent-protocol.md §2) — a job being dispatched
 * onto a queue (JobQueueing→JobQueued). `sync` connection jobs are skipped by the
 * sensor. `_group = xxh128(name)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class QueuedJobRecord
{
    public function __construct(
        public float $timestamp,
        public string $deploy,
        public string $server,
        public string $traceId,
        public string $executionId,
        public string $executionSource,
        public string $executionPreview,
        public string $executionStage,
        public string $user,
        public string $jobId,
        public string $name,
        public string $connection,
        public string $queue,
        public int $duration,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'queued-job',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::name($this->name),
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
            'job_id' => $this->jobId,
            'name' => Truncate::tiny($this->name),
            'connection' => Truncate::tiny($this->connection),
            'queue' => Truncate::tiny($this->queue),
            'duration' => $this->duration,
        ];
    }
}

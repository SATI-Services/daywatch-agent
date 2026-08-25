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
        public Envelope $envelope,
        public string $jobId,
        public string $name,
        public string $connection,
        public string $queue,
        public int $duration,
    ) {}

    public function toArray(): array
    {
        return $this->envelope->child('queued-job', Group::name($this->name)) + [
            'job_id' => $this->jobId,
            'name' => Truncate::tiny($this->name),
            'connection' => Truncate::tiny($this->connection),
            'queue' => Truncate::tiny($this->queue),
            'duration' => $this->duration,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `scheduled-task` record (daywatch-mcp/docs/agent-protocol.md §2) — one run of a
 * scheduled task. Its own execution under a fresh trace; carries the full
 * counters block and a flat duration. `_group = xxh128(name|cron|timezone)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class ScheduledTaskRecord
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
        public string $name,
        public string $cron,
        public string $timezone,
        public bool $withoutOverlapping,
        public bool $onOneServer,
        public bool $runInBackground,
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
            't' => 'scheduled-task',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::scheduledTask($this->name, $this->cron, $this->timezone),
            'trace_id' => $this->traceId,
            'user' => Truncate::tiny($this->user),
            'name' => Truncate::tiny($this->name),
            'cron' => Truncate::tiny($this->cron),
            'timezone' => Truncate::tiny($this->timezone),
            'without_overlapping' => $this->withoutOverlapping,
            'on_one_server' => $this->onOneServer,
            'run_in_background' => $this->runInBackground,
            'status' => $this->status,
            'duration' => $this->duration,
            ...Counters::normalize($this->counters),
            'peak_memory_usage' => $this->peakMemoryUsage,
            'exception_preview' => Truncate::tiny($this->exceptionPreview),
            'context' => Truncate::text($this->context),
        ];
    }
}

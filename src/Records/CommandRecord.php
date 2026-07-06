<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\ExecutionStage;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `command` record (daywatch-mcp/docs/agent-protocol.md §2) — an artisan command execution.
 * The command IS the execution root, so it carries `trace_id` + `user` but no
 * `execution_*` duplication, and walks the three command stages. `_group =
 * xxh128(name)`. Vendor/framework commands are not sampled by default.
 *
 * Field names are WIRE CONTRACT.
 */
final class CommandRecord
{
    /**
     * @param  array<string, int>  $stages  keyed by ExecutionStage::COMMAND_STAGES (µs)
     * @param  array<string, int>  $counters  keyed by Counters::KEYS
     */
    public function __construct(
        public float $timestamp,
        public string $deploy,
        public string $server,
        public string $traceId,
        public string $user,
        public string $class,
        public string $name,
        public string $command,
        public int $exitCode,
        public array $stages,
        public array $counters,
        public int $peakMemoryUsage,
        public string $exceptionPreview,
        public string $context,
    ) {}

    public function toArray(): array
    {
        $stages = [];
        foreach (ExecutionStage::COMMAND_STAGES as $stage) {
            $stages[$stage] = (int) ($this->stages[$stage] ?? 0);
        }

        return [
            'v' => 1,
            't' => 'command',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::name($this->name),
            'trace_id' => $this->traceId,
            'user' => Truncate::tiny($this->user),
            'class' => Truncate::tiny($this->class),
            'name' => Truncate::tiny($this->name),
            'command' => Truncate::text($this->command),
            'exit_code' => $this->exitCode,
            'duration' => array_sum($stages),
            'bootstrap' => $stages['bootstrap'],
            'action' => $stages['action'],
            'terminating' => $stages['terminating'],
            ...Counters::normalize($this->counters),
            'peak_memory_usage' => $this->peakMemoryUsage,
            'exception_preview' => Truncate::tiny($this->exceptionPreview),
            'context' => Truncate::text($this->context),
        ];
    }
}

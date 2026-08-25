<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Core;
use Daywatch\Agent\Support\Truncate;

/**
 * THE shared wire mapping. Every record on the wire opens with one of the three
 * envelope shapes below; each record DTO then appends only its own fields. Field
 * names, order, and truncation tiers here are WIRE CONTRACT — read the
 * `daywatch-payloads` skill before changing any of them.
 *
 * The three shapes (agent protocol §2, canonical sample in
 * samples/records.v1.json):
 *
 *   child(t, group)     v, t, timestamp, deploy, server, [_group,] trace_id,
 *                       execution_id, execution_source, execution_preview,
 *                       execution_stage, user
 *                       → query, exception, cache-event, outgoing-request,
 *                         queued-job, mail, notification, log (group: null)
 *
 *   execution(t, group) v, t, timestamp, deploy, server, _group, trace_id, user
 *                       → request, job-attempt, command, scheduled-task
 *                         (they ARE the execution, so they carry no execution_*;
 *                         their shared trailing block is {@see Counters::tail()})
 *
 *   minimal(t)          v, t, timestamp, deploy, server
 *                       → user
 *
 * Adding a record type therefore means: pick a shape, add the type's own fields,
 * and nothing else — there is no envelope to copy.
 */
final class Envelope
{
    /** Record schema version — the `v` field on every record. */
    public const VERSION = 1;

    public function __construct(
        public readonly float $timestamp,
        public readonly string $deploy = '',
        public readonly string $server = '',
        public readonly string $traceId = '',
        public readonly string $user = '',
        public readonly string $executionId = '',
        public readonly string $executionSource = '',
        public readonly string $executionPreview = '',
        public readonly string $executionStage = '',
    ) {}

    /**
     * Snapshot the current execution. The single place sensors get an envelope
     * from, so a sensor never restates the shared fields.
     */
    public static function for(Core $core, ?float $timestamp = null): self
    {
        return new self(
            timestamp: $timestamp ?? $core->clock()->microtime(),
            deploy: $core->deploy(),
            server: $core->server(),
            traceId: $core->traceId,
            user: $core->resolveUser(),
            executionId: $core->executionId,
            executionSource: $core->executionSource,
            executionPreview: $core->executionPreview,
            executionStage: $core->executionStage,
        );
    }

    /**
     * Head for a record recorded DURING an execution. `$group` of null omits
     * `_group` entirely (only `log` has no grouping recipe).
     *
     * @return array<string, mixed>
     */
    public function child(string $type, ?string $group = null): array
    {
        $head = $this->head($type);

        if ($group !== null) {
            $head['_group'] = $group;
        }

        return $head + [
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
        ];
    }

    /**
     * Head for a record that IS an execution (no execution_* self-reference).
     *
     * @return array<string, mixed>
     */
    public function execution(string $type, string $group): array
    {
        return $this->head($type) + [
            '_group' => $group,
            'trace_id' => $this->traceId,
            'user' => Truncate::tiny($this->user),
        ];
    }

    /**
     * Head for a record that belongs to no trace and no execution.
     *
     * @return array<string, mixed>
     */
    public function minimal(string $type): array
    {
        return $this->head($type);
    }

    /** @return array<string, mixed> */
    private function head(string $type): array
    {
        return [
            'v' => self::VERSION,
            't' => $type,
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
        ];
    }
}

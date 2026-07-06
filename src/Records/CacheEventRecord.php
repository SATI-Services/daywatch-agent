<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `cache-event` record (daywatch-mcp/docs/agent-protocol.md §2). Paired start/completion
 * cache events yield a `type` and a `duration`. `_group = xxh128(store|key)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class CacheEventRecord
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
        public string $store,
        public string $key,
        public string $type,
        public int $duration,
        public int $ttl,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'cache-event',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::cache($this->store, $this->key),
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
            'store' => Truncate::tiny($this->store),
            'key' => Truncate::tiny($this->key),
            'type' => $this->type,
            'duration' => $this->duration,
            'ttl' => $this->ttl,
        ];
    }
}

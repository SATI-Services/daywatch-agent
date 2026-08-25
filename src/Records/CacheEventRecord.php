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
        public Envelope $envelope,
        public string $store,
        public string $key,
        public string $type,
        public int $duration,
        public int $ttl,
    ) {}

    public function toArray(): array
    {
        return $this->envelope->child('cache-event', Group::cache($this->store, $this->key)) + [
            'store' => Truncate::tiny($this->store),
            'key' => Truncate::tiny($this->key),
            'type' => $this->type,
            'duration' => $this->duration,
            'ttl' => $this->ttl,
        ];
    }
}

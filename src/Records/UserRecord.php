<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Truncate;

/**
 * The `user` record (daywatch-mcp/docs/agent-protocol.md §2) — emitted once per execution when
 * an authenticated user is present. Envelope only: **no `_group`, `trace_id`, or
 * `execution_*`.** The user id also travels inline on every other record.
 *
 * Field names are WIRE CONTRACT.
 */
final class UserRecord
{
    public function __construct(
        public float $timestamp,
        public string $deploy,
        public string $server,
        public string $id,
        public string $name,
        public string $username,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'user',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            'id' => Truncate::tiny($this->id),
            'name' => Truncate::tiny($this->name),
            'username' => Truncate::tiny($this->username),
        ];
    }
}

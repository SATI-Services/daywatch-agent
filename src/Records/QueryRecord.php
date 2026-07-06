<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `query` record (apps/daywatch/docs/data-model.md §2). `timestamp` is the query START
 * (now − duration). `sql` is transmitted RAW with `?` placeholders — bindings
 * are NEVER substituted. `_group` uses the normalized SQL.
 *
 * Field names are WIRE CONTRACT.
 */
final class QueryRecord
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
        public string $sql,
        public string $file,
        public int $line,
        public int $duration,
        public string $connection,
        public string $connectionType,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'query',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::query($this->connection, $this->sql),
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
            'sql' => Truncate::medium($this->sql),
            'file' => Truncate::tiny($this->file),
            'line' => $this->line,
            'duration' => $this->duration,
            'connection' => Truncate::tiny($this->connection),
            'connection_type' => $this->connectionType,
        ];
    }
}

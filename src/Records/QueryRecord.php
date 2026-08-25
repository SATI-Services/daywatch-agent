<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `query` record (daywatch/docs/data-model.md §2). `timestamp` is the query START
 * (now − duration). `sql` is transmitted RAW with `?` placeholders — bindings
 * are NEVER substituted. `_group` uses the normalized SQL.
 *
 * Field names are WIRE CONTRACT.
 */
final class QueryRecord
{
    public function __construct(
        public Envelope $envelope,
        public string $sql,
        public string $file,
        public int $line,
        public int $duration,
        public string $connection,
        public string $connectionType,
    ) {}

    public function toArray(): array
    {
        return $this->envelope->child('query', Group::query($this->connection, $this->sql)) + [
            'sql' => Truncate::medium($this->sql),
            'file' => Truncate::tiny($this->file),
            'line' => $this->line,
            'duration' => $this->duration,
            'connection' => Truncate::tiny($this->connection),
            'connection_type' => $this->connectionType,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Truncate;

/**
 * The `log` record (daywatch-mcp/docs/agent-protocol.md §2). **No `_group`.** `message` has
 * its placeholders interpolated; `context` and `extra` are JSON strings.
 *
 * Field names are WIRE CONTRACT.
 */
final class LogRecord
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
        public string $level,
        public string $message,
        public string $context,
        public string $extra,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'log',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
            'level' => Truncate::tiny($this->level),
            'message' => Truncate::text($this->message),
            'context' => Truncate::text($this->context),
            'extra' => Truncate::text($this->extra),
        ];
    }
}

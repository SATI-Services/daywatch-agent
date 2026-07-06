<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `notification` record (daywatch-mcp/docs/agent-protocol.md §2) — a notification sent on
 * a channel (NotificationSending→NotificationSent). `_group = xxh128(class)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class NotificationRecord
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
        public string $channel,
        public string $class,
        public int $duration,
        public bool $failed,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'notification',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::name($this->class),
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
            'channel' => Truncate::tiny($this->channel),
            'class' => Truncate::tiny($this->class),
            'duration' => $this->duration,
            'failed' => $this->failed,
        ];
    }
}

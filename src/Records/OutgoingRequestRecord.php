<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `outgoing-request` record (daywatch-mcp/docs/agent-protocol.md §2) — an HTTP call made
 * by the app via the Laravel HTTP client. `url` has any userinfo stripped.
 * `_group = xxh128(host)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class OutgoingRequestRecord
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
        public string $host,
        public string $method,
        public string $url,
        public int $duration,
        public int $requestSize,
        public int $responseSize,
        public int $statusCode,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'outgoing-request',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::host($this->host),
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
            'host' => Truncate::tiny($this->host),
            'method' => Truncate::tiny($this->method),
            'url' => Truncate::text($this->url),
            'duration' => $this->duration,
            'request_size' => $this->requestSize,
            'response_size' => $this->responseSize,
            'status_code' => $this->statusCode,
        ];
    }
}

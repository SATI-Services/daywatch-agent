<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `outgoing-request` record (agent protocol §2) — an HTTP call made
 * by the app via the Laravel HTTP client. `url` has any userinfo stripped.
 * `_group = xxh128(host)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class OutgoingRequestRecord
{
    public function __construct(
        public Envelope $envelope,
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
        return $this->envelope->child('outgoing-request', Group::host($this->host)) + [
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

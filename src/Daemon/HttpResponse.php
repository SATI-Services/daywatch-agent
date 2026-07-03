<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Throwable;

/**
 * A minimal HTTP response the {@see IngestDispatcher} reasons over. Transport
 * agnostic: the production {@see SocketHttpSender} builds these from a raw socket
 * read; tests construct them directly.
 */
final class HttpResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $body = '',
    ) {}

    /** Decode the JSON body, tolerating garbage (returns [] on failure). */
    public function json(): array
    {
        try {
            $decoded = json_decode($this->body, true, 32, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return [];
        }
    }
}

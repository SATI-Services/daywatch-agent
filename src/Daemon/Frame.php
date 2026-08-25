<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

/**
 * A single decoded local-socket frame (agent protocol §4):
 *
 *   {length}:{version}:{token_hash}:{payload}
 *
 * `payload` is a JSON array of record objects, or a control literal: `PING`
 * (reachability probe) or `STATS` (counters snapshot).
 */
final class Frame
{
    public function __construct(
        public readonly string $version,
        public readonly string $tokenHash,
        public readonly string $payload,
    ) {}

    public function isPing(): bool
    {
        return $this->payload === 'PING';
    }

    public function isStats(): bool
    {
        return $this->payload === 'STATS';
    }
}

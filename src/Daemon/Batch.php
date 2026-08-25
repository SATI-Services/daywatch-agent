<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

/**
 * One gzipped batch in flight or awaiting retry. The `id` (Daywatch-Batch-Id) is
 * minted once and REUSED across retries so the server dedups redelivery via Redis
 * SETNX (agent protocol §6).
 */
final class Batch
{
    public int $attempts = 0;

    public function __construct(
        public readonly string $id,
        public readonly string $gzipBody,
        public readonly int $size,
        public readonly int $records = 0,
    ) {}
}

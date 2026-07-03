<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests\Support;

use Daywatch\Agent\Ingest\Client;
use RuntimeException;

/** {@see Client} double for command tests: canned ping/stats results, or throws. */
final class StubClient implements Client
{
    /**
     * @param  array<string, mixed>|null  $statsResult
     */
    public function __construct(
        private readonly bool $pingResult = true,
        private readonly bool $throw = false,
        private readonly ?array $statsResult = null,
    ) {}

    public function send(string $payload): bool
    {
        return true;
    }

    public function ping(): bool
    {
        if ($this->throw) {
            throw new RuntimeException('boom');
        }

        return $this->pingResult;
    }

    public function stats(): ?array
    {
        if ($this->throw) {
            throw new RuntimeException('boom');
        }

        return $this->statsResult;
    }
}

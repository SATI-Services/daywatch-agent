<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests\Support;

use Daywatch\Agent\Ingest\Client;
use RuntimeException;

/**
 * In-memory {@see Client} test double: records each digested payload string. Set
 * $throwOnSend to prove Core still swallows a transport that misbehaves.
 */
final class RecordingClient implements Client
{
    /** @var list<string> */
    public array $sent = [];

    public bool $throwOnSend = false;

    public function __construct(private readonly bool $pingResult = true) {}

    public function send(string $payload): bool
    {
        if ($this->throwOnSend) {
            throw new RuntimeException('transport exploded');
        }

        $this->sent[] = $payload;

        return true;
    }

    public function ping(): bool
    {
        return $this->pingResult;
    }

    public function stats(): ?array
    {
        return null;
    }

    /** @return array<int, array<string, mixed>> the last payload decoded */
    public function lastDecoded(): array
    {
        return json_decode($this->sent[array_key_last($this->sent)], true);
    }
}

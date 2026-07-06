<?php

declare(strict_types=1);

namespace Daywatch\Agent\Ingest;

/**
 * The app-side transport to the local daemon. Implementations MUST swallow every
 * failure and return false — a digest can never throw into the host app.
 */
interface Client
{
    /** Send a JSON payload (array of record objects). Returns true on `2:OK` ack. */
    public function send(string $payload): bool;

    /** Send a PING frame (daemon reachability probe). Returns true on `2:OK` ack. */
    public function ping(): bool;

    /**
     * Send a STATS frame and read the daemon's counters reply
     * (daywatch-mcp/docs/agent-protocol.md §4). Returns the decoded counters, or null on ANY
     * failure — dead daemon, no reply (pre-STATS daemon or token mismatch),
     * timeout, malformed JSON. Never throws.
     *
     * @return array<string, mixed>|null
     */
    public function stats(): ?array;
}

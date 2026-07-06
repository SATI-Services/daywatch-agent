<?php

declare(strict_types=1);

namespace Daywatch\Agent\Ingest;

/**
 * The local socket frame (daywatch-mcp/docs/agent-protocol.md §4):
 *
 *   {length}:{version}:{token_hash}:{payload}
 *
 * where `length` is the byte length of everything after the FIRST colon, i.e.
 * strlen("{version}:{token_hash}:{payload}"). `version` is literally `v1`.
 * `token_hash` is the first 7 chars of xxh128(token). This is WIRE CONTRACT.
 */
final class Payload
{
    public const VERSION = 'v1';

    public const ACK = '2:OK';

    public const PING = 'PING';

    public const STATS = 'STATS';

    /** Compute the 7-char token hash both sides derive from the shared token. */
    public static function tokenHash(?string $token): string
    {
        return substr(hash('xxh128', (string) $token), 0, 7);
    }

    /** Build a complete frame ready to write to the socket. */
    public static function frame(string $body, string $tokenHash, string $version = self::VERSION): string
    {
        $rest = $version.':'.$tokenHash.':'.$body;

        return strlen($rest).':'.$rest;
    }
}

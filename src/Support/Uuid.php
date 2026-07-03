<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

/**
 * Dependency-free UUIDv4 generator. Avoids pulling ramsey/uuid into the host's
 * dependency graph — the collector stays headless and minimal.
 */
final class Uuid
{
    public static function v4(): string
    {
        $bytes = random_bytes(16);

        // Set version (4) and variant (RFC 4122) bits.
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}

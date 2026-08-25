<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

/**
 * Byte-cap truncation tiers (agent protocol §1). Caps are enforced
 * app-side, before buffering, in BYTES not characters. Any multibyte sequence
 * split by truncation is repaired at encode time by JSON_INVALID_UTF8_SUBSTITUTE.
 */
final class Truncate
{
    /** tinyText — 255 bytes. */
    public const TINY = 255;

    /** text — 65,535 bytes. */
    public const TEXT = 65_535;

    /** mediumText — 16,777,215 bytes. */
    public const MEDIUM = 16_777_215;

    public static function tiny(?string $value): string
    {
        return self::bytes((string) $value, self::TINY);
    }

    public static function text(?string $value): string
    {
        return self::bytes((string) $value, self::TEXT);
    }

    public static function medium(?string $value): string
    {
        return self::bytes((string) $value, self::MEDIUM);
    }

    public static function bytes(string $value, int $limit): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit);
    }
}

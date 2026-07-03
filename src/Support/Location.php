<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

use Throwable;

/**
 * Normalizes absolute filesystem paths to app-relative form for the `file`
 * fields on query/exception records. Never throws.
 */
final class Location
{
    private static ?string $basePath = null;

    public static function appRelative(string $path): string
    {
        try {
            $base = self::basePath();

            if ($base !== '' && str_starts_with($path, $base)) {
                return ltrim(substr($path, strlen($base)), '/\\');
            }
        } catch (Throwable) {
            // fall through to the raw path
        }

        return $path;
    }

    /** Test seam: override the detected base path. */
    public static function setBasePath(?string $path): void
    {
        self::$basePath = $path;
    }

    private static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        if (function_exists('base_path')) {
            return self::$basePath = (string) base_path();
        }

        return self::$basePath = '';
    }
}

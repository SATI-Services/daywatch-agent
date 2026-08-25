<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Location;
use SplFileObject;
use Throwable;

/**
 * Builds the exception `trace` field: a JSON array of frames
 *   {file:"path:line", source:"Class->method(argTypes)", code:{lineNo:"src"}|null}
 * with ≤10 source-inlined frames, ±5 context lines, app files only, gated by
 * `capture_exception_source_code` (agent protocol §2). Never throws.
 */
final class ExceptionTrace
{
    private const MAX_SOURCE_FRAMES = 10;

    private const CONTEXT_LINES = 5;

    private const MAX_FILE_BYTES = 2_000_000;

    public static function build(Throwable $e, bool $captureSource): string
    {
        try {
            $trace = $e->getTrace();
            $frames = [];
            $sourceFrames = 0;

            foreach ($trace as $i => $frame) {
                // Frame N pairs the CALL SITE (frame 0 = the throw site) with the
                // source of the function invoked there.
                $file = $i === 0 ? $e->getFile() : ($trace[$i - 1]['file'] ?? '');
                $line = $i === 0 ? $e->getLine() : (int) ($trace[$i - 1]['line'] ?? 0);

                $code = null;
                if ($captureSource
                    && $sourceFrames < self::MAX_SOURCE_FRAMES
                    && $file !== ''
                    && $line > 0
                    && self::isAppFile($file)) {
                    $code = self::readContext($file, $line);
                    if ($code !== null) {
                        $sourceFrames++;
                    }
                }

                $frames[] = [
                    'file' => ($file !== '' ? Location::appRelative($file) : '').':'.$line,
                    'source' => self::formatSource($frame),
                    'code' => $code,
                ];
            }

            $json = json_encode(
                $frames,
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
            );

            return $json === false ? '[]' : $json;
        } catch (Throwable) {
            return '[]';
        }
    }

    private static function formatSource(array $frame): string
    {
        $function = (string) ($frame['function'] ?? '');
        $class = (string) ($frame['class'] ?? '');
        $type = (string) ($frame['type'] ?? '');

        $args = array_map(self::argType(...), $frame['args'] ?? []);
        $signature = $function.'('.implode(', ', $args).')';

        return $class !== '' ? $class.$type.$signature : $signature;
    }

    private static function argType(mixed $arg): string
    {
        if (is_object($arg)) {
            $class = $arg::class;
            $pos = strrpos($class, '\\');

            return $pos === false ? $class : substr($class, $pos + 1);
        }

        return match (gettype($arg)) {
            'integer' => 'int',
            'double' => 'float',
            'boolean' => 'bool',
            'NULL' => 'null',
            default => gettype($arg),
        };
    }

    private static function isAppFile(string $file): bool
    {
        $relative = Location::appRelative($file);

        // Still absolute → outside the app root; or under vendor/ → framework.
        return $relative !== $file && ! str_starts_with($relative, 'vendor/');
    }

    /** @return array<int, string>|null map of lineNo => source line */
    private static function readContext(string $file, int $line): ?array
    {
        try {
            if (! is_file($file) || ! is_readable($file)) {
                return null;
            }

            if ((int) @filesize($file) > self::MAX_FILE_BYTES) {
                return null;
            }

            $start = max(1, $line - self::CONTEXT_LINES);
            $end = $line + self::CONTEXT_LINES;

            $reader = new SplFileObject($file);
            $context = [];

            $reader->seek($start - 1);
            for ($n = $start; $n <= $end && ! $reader->eof(); $n++) {
                $context[$n] = rtrim((string) $reader->current(), "\r\n");
                $reader->next();
            }

            return $context === [] ? null : $context;
        } catch (Throwable) {
            return null;
        }
    }
}

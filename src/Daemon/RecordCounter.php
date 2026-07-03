<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

/**
 * Counts the records in a digest payload (a `[{...},{...}]` JSON array string)
 * WITHOUT decoding it — the daemon never re-parses record JSON. Counting happens
 * once, at the frame boundary, by scanning for top-level `{` outside JSON strings:
 * a single pass driven by C-speed primitives (strcspn/strpos jumps), no allocation,
 * no parse tree. Nested objects/arrays (e.g. an exception `trace`) sit inside their
 * record's braces, so only record roots are counted.
 */
final class RecordCounter
{
    /** Count top-level JSON objects in the payload. Garbage counts as 0-N, never throws. */
    public static function count(string $payload): int
    {
        $count = 0;
        $depth = 0;
        $length = strlen($payload);
        $i = 0;

        while ($i < $length) {
            // Jump to the next structural char we care about: '"', '{' or '}'.
            $i += strcspn($payload, '"{}', $i);

            if ($i >= $length) {
                break;
            }

            $char = $payload[$i];

            if ($char === '{') {
                if ($depth === 0) {
                    $count++;
                }

                $depth++;
                $i++;
            } elseif ($char === '}') {
                if ($depth > 0) {
                    $depth--;
                }

                $i++;
            } else {
                $i = self::skipString($payload, $i + 1, $length);
            }
        }

        return $count;
    }

    /**
     * Advance past a JSON string: find the closing quote, honouring backslash
     * escapes (a quote preceded by an ODD run of backslashes is escaped). Returns
     * the offset just past the closing quote, or $length for an unterminated string.
     */
    private static function skipString(string $payload, int $i, int $length): int
    {
        while ($i < $length) {
            $close = strpos($payload, '"', $i);

            if ($close === false) {
                return $length;
            }

            $backslashes = 0;
            $j = $close - 1;

            while ($j >= $i && $payload[$j] === '\\') {
                $backslashes++;
                $j--;
            }

            $i = $close + 1;

            if ($backslashes % 2 === 0) {
                return $i;
            }
        }

        return $length;
    }
}

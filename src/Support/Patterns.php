<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

use Illuminate\Support\Str;

/**
 * Patterns — a precompiled `Str::is()` pattern list backing the per-sensor
 * `ignore` options (`daywatch.sensors.*.ignore`: cache keys, query SQL, job names).
 *
 * The patterns are split ONCE, at construction. These filters run on hot
 * host paths (every cache event, every query, every job dispatch) and
 * Str::is() compiles a fresh regex per call, so the overwhelmingly common
 * shape — a plain `*substring*` glob like the `*illuminate:*` cache-key
 * default — is reduced to a str_contains needle and Str::is() is kept only
 * for patterns with real glob structure.
 */
final class Patterns
{
    /** @var list<string> Plain `*substring*` patterns, reduced to a str_contains needle. */
    private array $needles = [];

    /** @var list<string> Patterns with real glob structure, matched with Str::is(). */
    private array $globs = [];

    /**
     * @param  list<string>  $patterns  `Str::is()` patterns; a matching subject is ignored
     */
    public function __construct(array $patterns)
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/^\*([^*?\[\]]+)\*$/', $pattern, $m) === 1) {
                $this->needles[] = $m[1];

                continue;
            }

            $this->globs[] = $pattern;
        }
    }

    /**
     * Normalise an ignore-pattern option. Accepts an array (published config)
     * or a comma-separated string (an env var, which can only be a string);
     * blanks are dropped, so an empty value disables filtering.
     */
    public static function from(mixed $value): self
    {
        $parts = is_array($value) ? $value : explode(',', (string) $value);

        return new self(array_values(array_filter(array_map(
            static fn (mixed $p): string => trim((string) $p),
            $parts,
        ), static fn (string $p): bool => $p !== '')));
    }

    /** Does the subject match one of the patterns? */
    public function matches(string $subject): bool
    {
        foreach ($this->needles as $needle) {
            if (str_contains($subject, $needle)) {
                return true;
            }
        }

        return $this->globs !== [] && Str::is($this->globs, $subject);
    }
}

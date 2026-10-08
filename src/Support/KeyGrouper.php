<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

use Throwable;

/**
 * KeyGrouper — collapses high-cardinality strings (cache keys today) into
 * group labels, mirroring Laravel Pulse's `Recorders\Concerns\Groups`
 * semantics: a map of regex pattern ⇒ replacement applied with preg_replace;
 * the FIRST pattern that matches wins and unmatched values pass through
 * unchanged. The replacement may reference capture groups (`user:$1:*`).
 *
 * The map is config-file-only (an associative pattern ⇒ label map can't come
 * from an env var) and ships EMPTY — capture everything raw unless the host
 * opts in. A bad pattern must never break — or spam warnings into — the host
 * (the cardinal rule): every pattern is compile-checked ONCE at construction
 * (invalid ones are dropped), so the hot path only ever runs valid regexes;
 * the null check in group() covers subject-level failures (e.g. invalid
 * UTF-8 under a /u pattern).
 */
final class KeyGrouper
{
    /** @var array<string, string> */
    private array $groups = [];

    /**
     * @param  array<string, string>  $groups  regex pattern ⇒ replacement label
     */
    public function __construct(array $groups = [])
    {
        foreach ($groups as $pattern => $replacement) {
            if ($this->compiles($pattern)) {
                $this->groups[$pattern] = $replacement;
            }
        }
    }

    /**
     * Normalise a groups option: only a string ⇒ string map is honoured;
     * anything else (a list, an env-string, null) disables grouping.
     */
    public static function from(mixed $value): self
    {
        if (! is_array($value)) {
            return new self;
        }

        $groups = [];

        foreach ($value as $pattern => $replacement) {
            if (is_string($pattern) && is_string($replacement) && $pattern !== '') {
                $groups[$pattern] = $replacement;
            }
        }

        return new self($groups);
    }

    /** Map the value to its group label, or return it unchanged. */
    public function group(string $value): string
    {
        foreach ($this->groups as $pattern => $replacement) {
            try {
                $group = preg_replace($pattern, $replacement, $value, -1, $count);
            } catch (Throwable) {
                continue; // belt-and-braces — patterns were compile-checked
            }

            if ($group !== null && $count > 0) {
                return $group;
            }
        }

        return $value;
    }

    /** Compile-check without leaking a PHP warning or exception into the host. */
    private function compiles(string $pattern): bool
    {
        try {
            return @preg_match($pattern, '') !== false;
        } catch (Throwable) {
            return false; // e.g. ValueError on an empty pattern
        }
    }
}

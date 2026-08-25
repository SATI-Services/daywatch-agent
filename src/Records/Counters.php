<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Truncate;

/**
 * The twelve child-event counters carried by every execution-context record
 * (request / job-attempt / command / scheduled-task). All twelve are ALWAYS
 * present on the wire; unwired sensors leave their counter at 0.
 */
final class Counters
{
    public const KEYS = [
        'exceptions',
        'logs',
        'queries',
        'lazy_loads',
        'jobs_queued',
        'mail',
        'notifications',
        'outgoing_requests',
        'files_read',
        'files_written',
        'cache_events',
        'hydrated_models',
    ];

    /** A fresh zeroed counter map, keys in canonical order. */
    public static function zeroed(): array
    {
        return array_fill_keys(self::KEYS, 0);
    }

    /**
     * The shared TRAILING block of every execution-root record (request,
     * job-attempt, command, scheduled-task): the twelve counters followed by
     * `peak_memory_usage`, `exception_preview`, `context`. WIRE CONTRACT — see
     * {@see Envelope} for the matching head shapes.
     *
     * @param  array<string, int>  $counters
     * @return array<string, mixed>
     */
    public static function tail(
        array $counters,
        int $peakMemoryUsage,
        string $exceptionPreview,
        string $context,
    ): array {
        return self::normalize($counters) + [
            'peak_memory_usage' => $peakMemoryUsage,
            'exception_preview' => Truncate::tiny($exceptionPreview),
            'context' => Truncate::text($context),
        ];
    }

    /** Coerce an arbitrary map to exactly the canonical keys, zero-filled, ints. */
    public static function normalize(array $counters): array
    {
        $out = [];

        foreach (self::KEYS as $key) {
            $out[$key] = (int) ($counters[$key] ?? 0);
        }

        return $out;
    }
}

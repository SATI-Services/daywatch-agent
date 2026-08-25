<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

use Illuminate\Support\Facades\Context;
use Throwable;

/**
 * Propagates the trace id, sampling decision, and user id across queue hops via
 * hidden Laravel Context keys (agent protocol §3). Hidden keys ride with
 * the serialized job and are read back when the job runs, so a trace spans hops
 * with zero payload parsing. All access is guarded — Context may be unavailable
 * or unbootable, and telemetry propagation must never throw.
 */
final class ContextPropagation
{
    public const TRACE_ID = 'daywatch_trace_id';

    public const SHOULD_SAMPLE = 'daywatch_should_sample';

    public const USER_ID = 'daywatch_user_id';

    public static function write(string $traceId, bool $shouldSample, string $userId): void
    {
        try {
            if (! class_exists(Context::class)) {
                return;
            }

            Context::addHidden(self::TRACE_ID, $traceId);
            Context::addHidden(self::SHOULD_SAMPLE, $shouldSample);
            Context::addHidden(self::USER_ID, $userId);
        } catch (Throwable) {
            // Propagation is best-effort.
        }
    }

    /**
     * @return array{trace_id: ?string, should_sample: ?bool, user_id: ?string}
     */
    public static function read(): array
    {
        $default = ['trace_id' => null, 'should_sample' => null, 'user_id' => null];

        try {
            if (! class_exists(Context::class)) {
                return $default;
            }

            $traceId = Context::getHidden(self::TRACE_ID);
            $shouldSample = Context::getHidden(self::SHOULD_SAMPLE);
            $userId = Context::getHidden(self::USER_ID);

            return [
                'trace_id' => is_string($traceId) ? $traceId : null,
                'should_sample' => is_bool($shouldSample) ? $shouldSample : null,
                'user_id' => is_string($userId) ? $userId : null,
            ];
        } catch (Throwable) {
            return $default;
        }
    }
}

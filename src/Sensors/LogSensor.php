<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\LogRecord;
use Throwable;

/**
 * LogSensor — Illuminate\Log\Events\MessageLogged → `log` record
 * (agent protocol §2). We hook the framework `MessageLogged` event rather
 * than installing a custom Monolog channel: simpler, more portable, and it
 * yields the same `log` record (`level`, `message`, `context`).
 *
 * The handler is pure — it never logs (that would re-enter this sensor and loop)
 * and, per the cardinal rule, never throws into the host app.
 */
final class LogSensor
{
    public function __construct(private Core $core) {}

    public function handle(object $event): void
    {
        try {
            $core = $this->core;

            $level = (string) ($event->level ?? '');
            $message = (string) ($event->message ?? '');
            $context = $event->context ?? [];

            $record = new LogRecord(
                envelope: Envelope::for($core, $core->clock()->microtime()),
                level: $level,
                message: $message,
                context: $this->encodeContext($context),
                extra: '{}',
            );

            $core->recordLog($record->toArray());
        } catch (Throwable) {
            // telemetry loss is acceptable; logging must never fail loudly
        }
    }

    /**
     * @param  mixed  $context
     */
    private function encodeContext($context): string
    {
        try {
            if (! is_array($context) || $context === []) {
                return '{}';
            }

            return json_encode(
                $context,
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
            );
        } catch (Throwable) {
            return '{}';
        }
    }
}

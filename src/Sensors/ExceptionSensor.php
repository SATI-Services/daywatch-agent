<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\ExceptionRecord;
use Daywatch\Agent\Records\ExceptionTrace;
use Daywatch\Agent\Support\Location;
use Throwable;

/**
 * ExceptionSensor — the framework `reportable` hook and Daywatch::report() both
 * funnel here to emit an `exception` record (daywatch/docs/data-model.md §2). Recording an
 * exception re-rolls sampling at the exception rate (handled in Core) so errors
 * escape sampled-out traces. `ViewException` is unwrapped to its cause.
 */
final class ExceptionSensor
{
    public function __construct(private Core $core) {}

    public function report(Throwable $e, bool $handled = true): void
    {
        try {
            $core = $this->core;

            if ($core->isIgnored($e)) {
                return;
            }

            $e = $this->unwrap($e);

            $class = $e::class;
            $message = $e->getMessage();

            $record = new ExceptionRecord(
                envelope: Envelope::for($core, $core->clock()->microtime()),
                class: $class,
                file: Location::appRelative($e->getFile()),
                line: $e->getLine(),
                message: $message,
                code: (string) $e->getCode(),
                trace: ExceptionTrace::build($e, $core->captureExceptionSourceCode()),
                handled: $handled,
                phpVersion: PHP_VERSION,
                laravelVersion: $this->laravelVersion(),
            );

            $core->recordException($record->toArray(), $class.': '.$message);
        } catch (Throwable) {
            // never let recording an error raise a new one into the host app
        }
    }

    private function unwrap(Throwable $e): Throwable
    {
        // ViewException wraps the real cause; report the cause.
        if (is_a($e, 'Illuminate\\View\\ViewException') && $e->getPrevious() !== null) {
            return $e->getPrevious();
        }

        return $e;
    }

    private function laravelVersion(): string
    {
        try {
            if (function_exists('app')) {
                return (string) app()->version();
            }
        } catch (Throwable) {
        }

        return '';
    }
}

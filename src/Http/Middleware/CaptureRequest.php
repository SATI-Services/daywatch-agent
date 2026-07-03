<?php

declare(strict_types=1);

namespace Daywatch\Agent\Http\Middleware;

use Closure;
use Daywatch\Agent\Sensors\RequestSensor;
use Throwable;

/**
 * Global middleware prepended to the stack. Its only job is to prepare the
 * execution as early as possible and close the bootstrap stage. It must be
 * transparent — our work is guarded so the request always proceeds.
 */
final class CaptureRequest
{
    public function __construct(private RequestSensor $sensor) {}

    public function handle(mixed $request, Closure $next): mixed
    {
        try {
            $this->sensor->start($this->laravelStart());
        } catch (Throwable) {
            // never block the request
        }

        return $next($request);
    }

    private function laravelStart(): ?float
    {
        return defined('LARAVEL_START') ? (float) LARAVEL_START : null;
    }
}

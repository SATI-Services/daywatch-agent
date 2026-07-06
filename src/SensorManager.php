<?php

declare(strict_types=1);

namespace Daywatch\Agent;

use Daywatch\Agent\Http\Middleware\CaptureRequest;
use Daywatch\Agent\Sensors\CacheEventSensor;
use Daywatch\Agent\Sensors\CommandSensor;
use Daywatch\Agent\Sensors\ExceptionSensor;
use Daywatch\Agent\Sensors\JobAttemptSensor;
use Daywatch\Agent\Sensors\LogSensor;
use Daywatch\Agent\Sensors\MailSensor;
use Daywatch\Agent\Sensors\NotificationSensor;
use Daywatch\Agent\Sensors\OutgoingRequestSensor;
use Daywatch\Agent\Sensors\QuerySensor;
use Daywatch\Agent\Sensors\QueuedJobSensor;
use Daywatch\Agent\Sensors\RequestSensor;
use Daywatch\Agent\Sensors\ScheduledTaskSensor;
use Daywatch\Agent\Support\Uuid;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Wires sensors to Laravel events. Every attach AND every fired-listener body is
 * independently guarded: if one hook fails to register the others still attach,
 * and if a listener throws when the event fires it is swallowed — the host app is
 * never affected (the cardinal rule — see daywatch-agent/CLAUDE.md). Sensor
 * singletons are resolved once at attach time (not per event) to keep the hot
 * path free of container look-ups.
 */
final class SensorManager
{
    public function __construct(private Application $app) {}

    public function register(): void
    {
        $this->safely($this->attachRequestSensor(...));
        $this->safely($this->attachQuerySensor(...));
        $this->safely($this->attachExceptionSensor(...));
        $this->safely($this->attachCacheSensor(...));
        $this->safely($this->attachLogSensor(...));
        $this->safely($this->attachMailSensor(...));
        $this->safely($this->attachNotificationSensor(...));
        $this->safely($this->attachOutgoingRequestSensor(...));
        $this->safely($this->attachQueueSensors(...));
        $this->safely($this->attachCommandSensor(...));
        $this->safely($this->attachScheduledTaskSensor(...));
    }

    private function attachRequestSensor(): void
    {
        $events = $this->events();
        $sensor = $this->app->make(RequestSensor::class);

        $this->on($events, 'Illuminate\\Routing\\Events\\RouteMatched', fn ($event) => $sensor->routeMatched($event));
        $this->on($events, 'Illuminate\\Routing\\Events\\PreparingResponse', fn () => $sensor->preparingResponse());
        $this->on($events, 'Illuminate\\Routing\\Events\\ResponsePrepared', fn () => $sensor->responsePrepared());
        $this->on($events, 'Illuminate\\Foundation\\Http\\Events\\RequestHandled', fn () => $sensor->requestHandled());

        $kernel = $this->app->make(HttpKernel::class);

        if (method_exists($kernel, 'prependMiddleware')) {
            $kernel->prependMiddleware(CaptureRequest::class);
        }

        if (method_exists($kernel, 'whenRequestLifecycleIsLongerThan')) {
            // -1ms threshold → the handler always runs at request termination.
            $kernel->whenRequestLifecycleIsLongerThan(-1, function ($startedAt, $request, $response) use ($sensor): void {
                try {
                    if (is_object($request) && is_object($response)) {
                        $sensor->finish($request, $response);
                    }
                } catch (Throwable) {
                }
            });
        } else {
            // Fallback for kernels without the lifecycle hook: build at RequestHandled.
            $this->on($events, 'Illuminate\\Foundation\\Http\\Events\\RequestHandled', function ($event) use ($sensor): void {
                if (isset($event->request, $event->response)) {
                    $sensor->finish($event->request, $event->response);
                }
            });
        }
    }

    private function attachQuerySensor(): void
    {
        $sensor = $this->app->make(QuerySensor::class);

        $this->on($this->events(), 'Illuminate\\Database\\Events\\QueryExecuted', fn ($event) => $sensor->handle($event));
    }

    private function attachExceptionSensor(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'reportable')) {
            $sensor = $this->app->make(ExceptionSensor::class);

            $handler->reportable(function (Throwable $e) use ($sensor): void {
                try {
                    $sensor->report($e, true);
                } catch (Throwable) {
                }
            });
        }
    }

    private function attachCacheSensor(): void
    {
        $events = $this->events();
        $sensor = $this->app->make(CacheEventSensor::class);

        foreach ([
            'Illuminate\\Cache\\Events\\RetrievingKey',
            'Illuminate\\Cache\\Events\\RetrievingManyKeys',
            'Illuminate\\Cache\\Events\\CacheHit',
            'Illuminate\\Cache\\Events\\CacheMissed',
            'Illuminate\\Cache\\Events\\WritingKey',
            'Illuminate\\Cache\\Events\\WritingManyKeys',
            'Illuminate\\Cache\\Events\\KeyWritten',
            'Illuminate\\Cache\\Events\\KeyWriteFailed',
            'Illuminate\\Cache\\Events\\ForgettingKey',
            'Illuminate\\Cache\\Events\\KeyForgotten',
            'Illuminate\\Cache\\Events\\KeyForgetFailed',
        ] as $event) {
            $this->on($events, $event, fn ($e) => $sensor->handle($e));
        }
    }

    private function attachLogSensor(): void
    {
        $sensor = $this->app->make(LogSensor::class);

        $this->on($this->events(), 'Illuminate\\Log\\Events\\MessageLogged', fn ($event) => $sensor->handle($event));
    }

    private function attachMailSensor(): void
    {
        $events = $this->events();
        $sensor = $this->app->make(MailSensor::class);

        $this->on($events, 'Illuminate\\Mail\\Events\\MessageSending', fn ($event) => $sensor->sending($event));
        $this->on($events, 'Illuminate\\Mail\\Events\\MessageSent', fn ($event) => $sensor->sent($event));
    }

    private function attachNotificationSensor(): void
    {
        $events = $this->events();
        $sensor = $this->app->make(NotificationSensor::class);

        $this->on($events, 'Illuminate\\Notifications\\Events\\NotificationSending', fn ($event) => $sensor->sending($event));
        $this->on($events, 'Illuminate\\Notifications\\Events\\NotificationSent', fn ($event) => $sensor->sent($event));
    }

    private function attachOutgoingRequestSensor(): void
    {
        if (! class_exists(Http::class)) {
            return;
        }

        $sensor = $this->app->make(OutgoingRequestSensor::class);

        Http::globalMiddleware($sensor->guzzleMiddleware());
    }

    /**
     * Queue plumbing: inject a stable job_id into the payload (trace propagation),
     * emit `queued-job` on dispatch, and `job-attempt` per worker execution.
     */
    private function attachQueueSensors(): void
    {
        $events = $this->events();

        if (class_exists(Queue::class)) {
            Queue::createPayloadUsing(function (): array {
                try {
                    return ['daywatch' => ['job_id' => Uuid::v4()]];
                } catch (Throwable) {
                    return [];
                }
            });
        }

        $queued = $this->app->make(QueuedJobSensor::class);
        $this->on($events, 'Illuminate\\Queue\\Events\\JobQueueing', fn ($event) => $queued->queueing($event));
        $this->on($events, 'Illuminate\\Queue\\Events\\JobQueued', fn ($event) => $queued->queued($event));

        $attempt = $this->app->make(JobAttemptSensor::class);
        $this->on($events, 'Illuminate\\Queue\\Events\\JobProcessing', fn ($event) => $attempt->processing($event));
        $this->on($events, 'Illuminate\\Queue\\Events\\JobProcessed', fn ($event) => $attempt->processed($event));
        $this->on($events, 'Illuminate\\Queue\\Events\\JobFailed', fn ($event) => $attempt->failed($event));
        $this->on($events, 'Illuminate\\Queue\\Events\\JobReleasedAfterException', fn ($event) => $attempt->released($event));
    }

    private function attachCommandSensor(): void
    {
        $events = $this->events();
        $sensor = $this->app->make(CommandSensor::class);

        $this->on($events, 'Illuminate\\Console\\Events\\CommandStarting', fn ($event) => $sensor->starting($event));
        $this->on($events, 'Illuminate\\Console\\Events\\CommandFinished', fn ($event) => $sensor->finished($event));
    }

    private function attachScheduledTaskSensor(): void
    {
        $events = $this->events();
        $sensor = $this->app->make(ScheduledTaskSensor::class);

        $this->on($events, 'Illuminate\\Console\\Events\\ScheduledTaskStarting', fn ($event) => $sensor->starting($event));
        $this->on($events, 'Illuminate\\Console\\Events\\ScheduledTaskFinished', fn ($event) => $sensor->finished($event));
        $this->on($events, 'Illuminate\\Console\\Events\\ScheduledTaskSkipped', fn ($event) => $sensor->skipped($event));
        $this->on($events, 'Illuminate\\Console\\Events\\ScheduledTaskFailed', fn ($event) => $sensor->failed($event));
    }

    private function events(): Dispatcher
    {
        return $this->app->make('events');
    }

    /**
     * Register a listener whose FIRED body is guarded — the event dispatcher does
     * not catch listener exceptions, so an unguarded body would reach the host.
     */
    private function on(Dispatcher $events, string $event, callable $handler): void
    {
        $events->listen($event, function (...$args) use ($handler): void {
            try {
                $handler(...$args);
            } catch (Throwable) {
            }
        });
    }

    private function safely(callable $attach): void
    {
        try {
            $attach();
        } catch (Throwable $e) {
            $this->log($e);
        }
    }

    private function log(Throwable $e): void
    {
        try {
            if ($this->app->bound('log')) {
                $this->app->make('log')->error('[daywatch] sensor wiring failed: '.$e->getMessage());
            }
        } catch (Throwable) {
        }
    }
}

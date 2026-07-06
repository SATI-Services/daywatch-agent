<?php

declare(strict_types=1);

namespace Daywatch\Agent;

use Daywatch\Agent\Http\Middleware\CaptureRequest;
use Daywatch\Agent\Sensors\ExceptionSensor;
use Daywatch\Agent\Sensors\QuerySensor;
use Daywatch\Agent\Sensors\RequestSensor;
use Daywatch\Agent\Support\Uuid;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * Wires sensors to Laravel events. Every attach is independently guarded: if one
 * hook fails to register, the others still attach and the host app is unaffected
 * (the cardinal rule — see packages/daywatch-agent/CLAUDE.md).
 */
final class SensorManager
{
    public function __construct(private Application $app) {}

    public function register(): void
    {
        $this->safely($this->attachRequestSensor(...));
        $this->safely($this->attachQuerySensor(...));
        $this->safely($this->attachExceptionSensor(...));
        $this->safely($this->attachQueuePropagation(...));
    }

    private function attachRequestSensor(): void
    {
        $events = $this->app->make('events');

        $events->listen('Illuminate\\Routing\\Events\\RouteMatched', function ($event): void {
            $this->app->make(RequestSensor::class)->routeMatched($event);
        });

        $events->listen('Illuminate\\Routing\\Events\\PreparingResponse', function (): void {
            $this->app->make(RequestSensor::class)->preparingResponse();
        });

        $events->listen('Illuminate\\Routing\\Events\\ResponsePrepared', function (): void {
            $this->app->make(RequestSensor::class)->responsePrepared();
        });

        $events->listen('Illuminate\\Foundation\\Http\\Events\\RequestHandled', function ($event): void {
            $this->app->make(RequestSensor::class)->requestHandled();
        });

        $kernel = $this->app->make(HttpKernel::class);

        if (method_exists($kernel, 'prependMiddleware')) {
            $kernel->prependMiddleware(CaptureRequest::class);
        }

        if (method_exists($kernel, 'whenRequestLifecycleIsLongerThan')) {
            // -1ms threshold → the handler always runs at request termination.
            $kernel->whenRequestLifecycleIsLongerThan(-1, function ($startedAt, $request, $response): void {
                $this->app->make(RequestSensor::class)->finish($request, $response);
            });
        } else {
            // Fallback for kernels without the lifecycle hook: build at RequestHandled.
            $events->listen('Illuminate\\Foundation\\Http\\Events\\RequestHandled', function ($event): void {
                if (isset($event->request, $event->response)) {
                    $this->app->make(RequestSensor::class)->finish($event->request, $event->response);
                }
            });
        }
    }

    private function attachQuerySensor(): void
    {
        $this->app->make('events')->listen(
            'Illuminate\\Database\\Events\\QueryExecuted',
            function ($event): void {
                $this->app->make(QuerySensor::class)->handle($event);
            }
        );
    }

    private function attachExceptionSensor(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'reportable')) {
            $handler->reportable(function (Throwable $e): void {
                $this->app->make(ExceptionSensor::class)->report($e, true);
            });
        }
    }

    /**
     * Queue-hop propagation plumbing (services/daywatch-mcp/docs/agent-protocol.md §3). trace/sampling/
     * user ride to jobs via the hidden Context keys written in Core; here we inject
     * a stable job_id into the payload and adopt the trace when a job runs. Full
     * job-attempt records are M4.
     */
    private function attachQueuePropagation(): void
    {
        $events = $this->app->make('events');

        if (class_exists('Illuminate\\Support\\Facades\\Queue')) {
            Queue::createPayloadUsing(function (): array {
                return ['daywatch' => ['job_id' => Uuid::v4()]];
            });
        }

        $events->listen('Illuminate\\Queue\\Events\\JobProcessing', function (): void {
            $this->app->make(Core::class)->prepareForJob();
        });

        foreach ([
            'Illuminate\\Queue\\Events\\JobProcessed',
            'Illuminate\\Queue\\Events\\JobFailed',
            'Illuminate\\Queue\\Events\\JobReleasedAfterException',
        ] as $event) {
            $events->listen($event, function (): void {
                $this->app->make(Core::class)->finishExecution();
            });
        }
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

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\RequestRecord;
use Daywatch\Agent\Support\ExecutionStage;
use Illuminate\Support\Facades\Context;
use Throwable;

/**
 * RequestSensor — captures the request lifecycle into a `request` record
 * (daywatch/docs/data-model.md §2). Stage boundaries are marked by the global middleware
 * + routing/response events; the record is assembled at request end (the kernel
 * lifecycle handler / RequestHandled) and the execution is digested or flushed.
 */
final class RequestSensor
{
    private StageSensor $stages;

    private UserSensor $users;

    public function __construct(private Core $core, ?StageSensor $stages = null, ?UserSensor $users = null)
    {
        $this->stages = $stages ?? new StageSensor($core);
        $this->users = $users ?? new UserSensor($core);
    }

    /** Global middleware entry: prepare the execution and close the bootstrap stage. */
    public function start(?float $startedAt = null): void
    {
        $this->core->prepareForRequest($startedAt);
        $this->stages->advance(ExecutionStage::BOOTSTRAP);
    }

    /** RouteMatched: set the execution preview and close before_middleware. */
    public function routeMatched(object $event): void
    {
        try {
            $route = $event->route ?? null;
            $request = $event->request ?? null;

            if ($route !== null && $request !== null && method_exists($request, 'getMethod')) {
                $this->core->setExecutionPreview(
                    $request->getMethod().' '.$this->routePath($route)
                );
            }
        } catch (Throwable) {
        }

        $this->stages->advance(ExecutionStage::BEFORE_MIDDLEWARE);
    }

    public function preparingResponse(): void
    {
        $this->stages->advance(ExecutionStage::ACTION);
    }

    public function responsePrepared(): void
    {
        $this->stages->advance(ExecutionStage::RENDER);
    }

    public function requestHandled(): void
    {
        $this->stages->advance(ExecutionStage::AFTER_MIDDLEWARE);
    }

    /** Request end: close remaining stages, build the record, digest/flush. */
    public function finish(object $request, object $response): void
    {
        try {
            $core = $this->core;

            $core->beginStage(ExecutionStage::SENDING);

            $record = new RequestRecord(
                timestamp: $core->requestStartedAt() ?: $core->clock()->microtime(),
                deploy: $core->deploy(),
                server: $core->server(),
                traceId: $core->traceId,
                user: $core->resolveUser(),
                method: $this->method($request),
                url: $this->url($request),
                routeName: $this->routeName($request),
                routeMethods: $this->routeMethods($request),
                routeDomain: $this->routeDomain($request),
                routePath: $this->requestRoutePath($request),
                routeAction: $this->routeAction($request),
                ip: $this->ip($request),
                statusCode: $this->statusCode($response),
                requestSize: $this->requestSize($request),
                responseSize: $this->responseSize($response),
                stages: $core->stages(),
                counters: $core->counters(),
                peakMemoryUsage: memory_get_peak_usage(true),
                exceptionPreview: $core->exceptionPreview(),
                context: $this->context(),
            );

            // Close terminating just before the record is finalised so its µs is
            // reflected; the stage sum determines the request duration.
            $core->beginStage(ExecutionStage::TERMINATING);
            $stages = $core->stages();
            $record->stages = $stages;

            $this->users->capture();
            $core->write($record->toArray());
            $core->finishExecution();
        } catch (Throwable) {
        }
    }

    private function method(object $request): string
    {
        return method_exists($request, 'getMethod') ? (string) $request->getMethod() : '';
    }

    private function url(object $request): string
    {
        try {
            if (method_exists($request, 'fullUrl')) {
                return (string) $request->fullUrl();
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function ip(object $request): string
    {
        try {
            if (method_exists($request, 'ip')) {
                return (string) ($request->ip() ?? '');
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function route(object $request): ?object
    {
        try {
            if (method_exists($request, 'route')) {
                $route = $request->route();

                return is_object($route) ? $route : null;
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function routeName(object $request): string
    {
        $route = $this->route($request);

        try {
            return $route && method_exists($route, 'getName') ? (string) ($route->getName() ?? '') : '';
        } catch (Throwable) {
            return '';
        }
    }

    /** @return array<int, string> */
    private function routeMethods(object $request): array
    {
        $route = $this->route($request);

        try {
            if ($route && method_exists($route, 'methods')) {
                return array_values(array_map('strval', $route->methods()));
            }
        } catch (Throwable) {
        }

        $method = $this->method($request);

        return $method === '' ? [] : [$method];
    }

    private function routeDomain(object $request): string
    {
        $route = $this->route($request);

        try {
            return $route && method_exists($route, 'getDomain') ? (string) ($route->getDomain() ?? '') : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function requestRoutePath(object $request): string
    {
        $route = $this->route($request);

        if ($route !== null) {
            return $this->routePath($route);
        }

        try {
            return method_exists($request, 'path') ? '/'.ltrim((string) $request->path(), '/') : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function routePath(object $route): string
    {
        try {
            if (method_exists($route, 'uri')) {
                return '/'.ltrim((string) $route->uri(), '/');
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function routeAction(object $request): string
    {
        $route = $this->route($request);

        try {
            return $route && method_exists($route, 'getActionName') ? (string) $route->getActionName() : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function statusCode(object $response): int
    {
        try {
            return method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private function requestSize(object $request): int
    {
        try {
            if (method_exists($request, 'header')) {
                $length = $request->header('Content-Length');
                if ($length !== null && $length !== '') {
                    return (int) $length;
                }
            }

            if (method_exists($request, 'getContent')) {
                return strlen((string) $request->getContent());
            }
        } catch (Throwable) {
        }

        return 0;
    }

    private function responseSize(object $response): int
    {
        try {
            if (method_exists($response, 'getContent')) {
                $content = $response->getContent();
                if ($content !== false) {
                    return strlen((string) $content);
                }
            }
        } catch (Throwable) {
        }

        return 0;
    }

    private function context(): string
    {
        try {
            if (class_exists(Context::class)) {
                $all = Context::all();

                $json = json_encode(
                    $all,
                    JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE,
                );

                return $json === false ? '{}' : $json;
            }
        } catch (Throwable) {
        }

        return '{}';
    }
}

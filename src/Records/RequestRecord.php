<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\ExecutionStage;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `request` record (apps/daywatch/docs/data-model.md §2). The request IS the execution
 * root, so it carries `trace_id` + `user` but no `execution_*` duplication.
 * `duration` == the sum of the seven stage durations.
 *
 * Field names are WIRE CONTRACT — do not add/rename without the daywatch-payloads
 * skill.
 */
final class RequestRecord
{
    /**
     * @param  array<int, string>  $routeMethods
     * @param  array<string, int>  $stages  keyed by ExecutionStage::REQUEST_STAGES (µs)
     * @param  array<string, int>  $counters  keyed by Counters::KEYS
     */
    public function __construct(
        public float $timestamp,
        public string $deploy,
        public string $server,
        public string $traceId,
        public string $user,
        public string $method,
        public string $url,
        public string $routeName,
        public array $routeMethods,
        public string $routeDomain,
        public string $routePath,
        public string $routeAction,
        public string $ip,
        public int $statusCode,
        public int $requestSize,
        public int $responseSize,
        public array $stages,
        public array $counters,
        public int $peakMemoryUsage,
        public string $exceptionPreview,
        public string $context,
    ) {}

    public function toArray(): array
    {
        $stages = [];
        foreach (ExecutionStage::REQUEST_STAGES as $stage) {
            $stages[$stage] = (int) ($this->stages[$stage] ?? 0);
        }

        return [
            'v' => 1,
            't' => 'request',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::request($this->routeMethods, $this->routeDomain, $this->routePath),
            'trace_id' => $this->traceId,
            'user' => Truncate::tiny($this->user),
            'method' => Truncate::tiny($this->method),
            'url' => Truncate::text($this->url),
            'route_name' => Truncate::tiny($this->routeName),
            'route_methods' => array_values($this->routeMethods),
            'route_domain' => Truncate::tiny($this->routeDomain),
            'route_path' => Truncate::tiny($this->routePath),
            'route_action' => Truncate::tiny($this->routeAction),
            'ip' => Truncate::tiny($this->ip),
            'duration' => array_sum($stages),
            'status_code' => $this->statusCode,
            'request_size' => $this->requestSize,
            'response_size' => $this->responseSize,
            'bootstrap' => $stages['bootstrap'],
            'before_middleware' => $stages['before_middleware'],
            'action' => $stages['action'],
            'render' => $stages['render'],
            'after_middleware' => $stages['after_middleware'],
            'sending' => $stages['sending'],
            'terminating' => $stages['terminating'],
            ...Counters::normalize($this->counters),
            'peak_memory_usage' => $this->peakMemoryUsage,
            'exception_preview' => Truncate::tiny($this->exceptionPreview),
            'context' => Truncate::text($this->context),
        ];
    }
}

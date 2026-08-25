<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\OutgoingRequestRecord;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * OutgoingRequestSensor — HTTP calls made by the app via the Laravel HTTP
 * client → `outgoing-request` record (daywatch-mcp/docs/agent-protocol.md §2).
 *
 * Mirrors laravel/nightwatch: a Guzzle middleware installed via
 * Http::globalMiddleware(...) times the promise and, on fulfilment, hands the
 * PSR-7 request/response pair back to {@see record()}. `url` has any userinfo
 * stripped; `_group = xxh128(host)`.
 *
 * CARDINAL RULE: this sensor never throws into the host app.
 */
final class OutgoingRequestSensor
{
    public function __construct(private Core $core) {}

    /**
     * Record a completed outgoing HTTP call from its PSR-7 request/response
     * pair and the wall-clock microtimes bracketing the call.
     */
    public function record(
        RequestInterface $request,
        ResponseInterface $response,
        float $startMicrotime,
        float $endMicrotime,
    ): void {
        try {
            $core = $this->core;

            $uri = $request->getUri();

            $record = new OutgoingRequestRecord(
                envelope: Envelope::for($core, $startMicrotime),
                host: $uri->getHost(),
                method: $request->getMethod(),
                url: (string) $uri->withUserInfo(''),
                duration: (int) round(($endMicrotime - $startMicrotime) * 1_000_000),
                requestSize: $this->resolveMessageSize($request),
                responseSize: $this->resolveMessageSize($response),
                statusCode: $response->getStatusCode(),
            );

            $core->recordOutgoingRequest($record->toArray());
        } catch (Throwable) {
            // telemetry loss is acceptable; an outgoing request must never fail loudly
        }
    }

    /**
     * Guzzle-style middleware factory for Http::globalMiddleware(...). Times the
     * promise and calls {@see record()} on fulfilment. Optional — the service
     * provider wires it; kept fully defensive so a broken handler chain can
     * never surface here.
     */
    public function guzzleMiddleware(): callable
    {
        return function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler) {
                $start = $this->core->clock()->microtime();

                $promise = $handler($request, $options);

                try {
                    return $promise->then(function (ResponseInterface $response) use ($request, $start) {
                        try {
                            $this->record($request, $response, $start, $this->core->clock()->microtime());
                        } catch (Throwable) {
                            // never let recording break the response pipeline
                        }

                        return $response;
                    });
                } catch (Throwable) {
                    return $promise;
                }
            };
        };
    }

    /**
     * Body size in bytes: prefer the stream size, fall back to Content-Length,
     * else 0. Never throws.
     */
    private function resolveMessageSize(MessageInterface $message): int
    {
        try {
            $size = $message->getBody()->getSize();

            if ($size !== null) {
                return (int) $size;
            }

            $header = $message->getHeaderLine('Content-Length');

            if ($header !== '' && is_numeric($header)) {
                return (int) $header;
            }
        } catch (Throwable) {
        }

        return 0;
    }
}

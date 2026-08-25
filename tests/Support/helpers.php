<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Core;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\RequestRecord;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\FrozenClock;
use Daywatch\Agent\Tests\Support\RecordingClient;

if (! function_exists('makeCore')) {
    /**
     * Build a Core wired to a {@see RecordingClient}, a real {@see RecordsBuffer},
     * and a {@see FrozenClock} — the deterministic rig for Core/sensor tests.
     *
     * @return array{0: Core, 1: RecordsBuffer, 2: FrozenClock}
     */
    function makeCore(
        RecordingClient $client,
        float $requestRate = 1.0,
        float $exceptionRate = 1.0,
        int $bufferLimit = 500,
        float $now = 1000.0,
    ): array {
        $buffer = new RecordsBuffer($bufferLimit);
        $clock = new FrozenClock($now);

        $core = new Core(
            buffer: $buffer,
            client: $client,
            clock: $clock,
            enabled: true,
            deploy: 'dep',
            server: 'srv',
            requestSampleRate: $requestRate,
            commandSampleRate: 1.0,
            exceptionSampleRate: $exceptionRate,
            captureExceptionSourceCode: true,
        );

        return [$core, $buffer, $clock];
    }
}

if (! function_exists('makeRequestRecord')) {
    /** The canonical request record fixture (mirrors daywatch-mcp/docs/samples/records.v1.json). */
    function makeRequestRecord(): RequestRecord
    {
        return new RequestRecord(
            envelope: new Envelope(
                timestamp: 1751446800.104217,
                deploy: 'abc1234',
                server: 'demo-01',
                traceId: '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
                user: '42',
            ),
            method: 'GET',
            url: 'https://demo.test/orders/1042',
            routeName: 'orders.show',
            routeMethods: ['GET', 'HEAD'],
            routeDomain: '',
            routePath: '/orders/{order}',
            routeAction: 'App\\Http\\Controllers\\OrderController@show',
            ip: '192.168.65.1',
            statusCode: 200,
            requestSize: 512,
            responseSize: 14208,
            stages: [
                'bootstrap' => 24000, 'before_middleware' => 6000, 'action' => 132000,
                'render' => 11000, 'after_middleware' => 4000, 'sending' => 5000, 'terminating' => 2000,
            ],
            counters: [
                'exceptions' => 1, 'logs' => 1, 'queries' => 1, 'jobs_queued' => 1,
                'outgoing_requests' => 1, 'cache_events' => 1, 'hydrated_models' => 3,
            ],
            peakMemoryUsage: 26214400,
            exceptionPreview: 'App\\Exceptions\\PaymentRetried: Retrying charge',
            context: '{"tenant":"acme"}',
        );
    }
}

if (! function_exists('requestGroupHash')) {
    function requestGroupHash(): string
    {
        return Group::request(['GET', 'HEAD'], '', '/orders/{order}');
    }
}

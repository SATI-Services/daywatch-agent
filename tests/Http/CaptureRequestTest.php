<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Core;
use Daywatch\Agent\Http\Middleware\CaptureRequest;
use Daywatch\Agent\Sensors\RequestSensor;
use Daywatch\Agent\Support\ExecutionStage;
use Daywatch\Agent\Tests\Support\FrozenClock;
use Daywatch\Agent\Tests\Support\RecordingClient;

/**
 * The global middleware is the request sensor's entry point (prepended to the
 * HTTP kernel by SensorManager::attachRequestSensor). Beyond starting the
 * execution its one job is the cardinal rule: the telemetry work is guarded
 * so the request ALWAYS proceeds, whatever the sensor does.
 *
 * The LARAVEL_START-undefined branch (laravelStart() → null) is not exercisable
 * in-process — a defined constant cannot be undefined — so prepareForRequest's
 * own `?? microtime` fallback covers it.
 */
function makeCaptureMiddleware(Core $core): CaptureRequest
{
    return new CaptureRequest(new RequestSensor($core));
}

it('starts the request execution and hands the request to the next middleware', function () {
    [$core] = makeCore(new RecordingClient);

    $request = (object) ['path' => '/orders'];
    $seen = null;

    $response = makeCaptureMiddleware($core)->handle($request, function ($passed) use (&$seen) {
        $seen = $passed;

        return 'response-sentinel';
    });

    expect($response)->toBe('response-sentinel')
        ->and($seen)->toBe($request)
        ->and($core->executionSource)->toBe('request')
        ->and($core->executionStage)->toBe(ExecutionStage::BEFORE_MIDDLEWARE) // bootstrap closed
        ->and($core->stages())->toHaveKey('bootstrap')
        ->and($core->stages()['bootstrap'])->toBeInt()->toBeGreaterThanOrEqual(0);
});

it('never blocks the request when the sensor explodes', function () {
    $core = new class(new RecordsBuffer(1), new RecordingClient, new FrozenClock(1000.0)) extends Core
    {
        public function prepareForRequest(?float $startedAt = null): void
        {
            throw new RuntimeException('sensor exploded');
        }
    };

    $response = makeCaptureMiddleware($core)->handle((object) [], fn () => 'still-served');

    expect($response)->toBe('still-served');
});

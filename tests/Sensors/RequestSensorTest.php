<?php

declare(strict_types=1);

use Daywatch\Agent\Records\Counters;
use Daywatch\Agent\Sensors\RequestSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;

it('assembles a request record with integer-microsecond stage durations', function () {
    $client = new RecordingClient;
    [$core, , $clock] = makeCore($client, requestRate: 1.0, now: 1000.0);

    $route = new Route(['GET', 'HEAD'], 'orders/{order}', ['uses' => fn () => null]);
    $request = Request::create('https://demo.test/orders/1042', 'GET');
    $request->setRouteResolver(fn () => $route);
    $response = new Response('hello', 200);

    $sensor = new RequestSensor($core);

    $clock->set(1000.024);
    $sensor->start(1000.0);                 // bootstrap = 24 000 µs
    $traceId = $core->traceId;

    $clock->set(1000.030);
    $sensor->routeMatched((object) ['route' => $route, 'request' => $request]); // before_middleware = 6 000

    $clock->set(1000.162);
    $sensor->preparingResponse();           // action = 132 000

    $clock->set(1000.173);
    $sensor->responsePrepared();            // render = 11 000

    $clock->set(1000.177);
    $sensor->requestHandled();              // after_middleware = 4 000

    $clock->set(1000.182);
    $sensor->finish($request, $response);   // sending = 5 000, terminating = 0

    $record = $client->lastDecoded()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('request')
        ->and($record['method'])->toBe('GET')
        ->and($record['status_code'])->toBe(200)
        ->and($record['response_size'])->toBe(5)
        ->and($record['route_methods'])->toBe(['GET', 'HEAD'])
        ->and($record['route_path'])->toBe('/orders/{order}')
        ->and($record['route_domain'])->toBe('')
        ->and($record['trace_id'])->toBe($traceId)
        ->and($record['_group'])->toBe(Group::request(['GET', 'HEAD'], '', '/orders/{order}'))
        ->and($record['bootstrap'])->toBe(24000)
        ->and($record['before_middleware'])->toBe(6000)
        ->and($record['action'])->toBe(132000)
        ->and($record['render'])->toBe(11000)
        ->and($record['after_middleware'])->toBe(4000)
        ->and($record['sending'])->toBe(5000)
        ->and($record['terminating'])->toBe(0)
        ->and($record['duration'])->toBe(182000)
        ->and($record['peak_memory_usage'])->toBeInt();

    foreach (Counters::KEYS as $counter) {
        expect($record)->toHaveKey($counter);
    }
});

it('sets duration to the exact sum of the seven stage durations', function () {
    $client = new RecordingClient;
    [$core, , $clock] = makeCore($client, requestRate: 1.0, now: 1000.0);

    $request = Request::create('https://demo.test/health', 'GET');
    $response = new Response('', 204);
    $sensor = new RequestSensor($core);

    $clock->set(1000.010);
    $sensor->start(1000.0);
    $clock->set(1000.050);
    $sensor->finish($request, $response);

    $record = $client->lastDecoded()[0];
    $sum = $record['bootstrap'] + $record['before_middleware'] + $record['action']
        + $record['render'] + $record['after_middleware'] + $record['sending'] + $record['terminating'];

    expect($record['duration'])->toBe($sum);
});

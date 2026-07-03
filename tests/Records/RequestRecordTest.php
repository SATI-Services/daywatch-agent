<?php

declare(strict_types=1);

use Daywatch\Agent\Support\Group;

it('serializes the exact request wire payload', function () {
    expect(makeRequestRecord()->toArray())->toBe([
        'v' => 1,
        't' => 'request',
        'timestamp' => 1751446800.104217,
        'deploy' => 'abc1234',
        'server' => 'demo-01',
        '_group' => Group::request(['GET', 'HEAD'], '', '/orders/{order}'),
        'trace_id' => '0e8b1a2c-6f4d-4b7e-9c3a-5d2e8f1a0b4c',
        'user' => '42',
        'method' => 'GET',
        'url' => 'https://demo.test/orders/1042',
        'route_name' => 'orders.show',
        'route_methods' => ['GET', 'HEAD'],
        'route_domain' => '',
        'route_path' => '/orders/{order}',
        'route_action' => 'App\\Http\\Controllers\\OrderController@show',
        'ip' => '192.168.65.1',
        'duration' => 184000,
        'status_code' => 200,
        'request_size' => 512,
        'response_size' => 14208,
        'bootstrap' => 24000,
        'before_middleware' => 6000,
        'action' => 132000,
        'render' => 11000,
        'after_middleware' => 4000,
        'sending' => 5000,
        'terminating' => 2000,
        'exceptions' => 1,
        'logs' => 1,
        'queries' => 1,
        'lazy_loads' => 0,
        'jobs_queued' => 1,
        'mail' => 0,
        'notifications' => 0,
        'outgoing_requests' => 1,
        'files_read' => 0,
        'files_written' => 0,
        'cache_events' => 1,
        'hydrated_models' => 3,
        'peak_memory_usage' => 26214400,
        'exception_preview' => 'App\\Exceptions\\PaymentRetried: Retrying charge',
        'context' => '{"tenant":"acme"}',
    ]);
});

it('sets duration to the sum of the seven stage durations', function () {
    expect(makeRequestRecord()->toArray()['duration'])->toBe(24000 + 6000 + 132000 + 11000 + 4000 + 5000 + 2000);
});

it('always emits all twelve counters even when unwired', function () {
    $keys = array_keys(makeRequestRecord()->toArray());

    foreach (['lazy_loads', 'mail', 'notifications', 'files_read', 'files_written'] as $zeroed) {
        expect($keys)->toContain($zeroed);
    }
});

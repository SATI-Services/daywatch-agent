<?php

declare(strict_types=1);

use Daywatch\Agent\Ingest\Client;
use Daywatch\Agent\Tests\Support\StubClient;
use Illuminate\Support\Facades\Artisan;

function daemonCounters(float $lastFlushAt = 1751540400.5): array
{
    return [
        'base_url' => 'https://daywatch.example.com',
        'records_received' => 150,
        'records_buffered' => 124,
        'buffered_bytes' => 51_200,
        'batches_sent' => 42,
        'records_sent' => 10_500,
        'send_failures' => 0,
        'retries' => 0,
        'last_flush_at' => $lastFlushAt,
        'last_flush_records' => 26,
    ];
}

it('renders the daemon counters as a table when STATS replies', function () {
    $counters = daemonCounters(microtime(true) - 3);
    $this->app->instance(Client::class, new StubClient(statsResult: $counters));

    $this->withoutMockingConsoleOutput();
    $exit = $this->artisan('daywatch:status');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('reachable at')
        ->toContain('base_url')
        ->toContain('https://daywatch.example.com')
        ->toContain('records_buffered')
        ->toContain('124')
        ->toContain('records_sent')
        ->toContain('10500')
        ->toContain('s ago)');
});

it('renders never for a daemon that has not flushed yet', function () {
    $this->app->instance(Client::class, new StubClient(statsResult: array_merge(daemonCounters(), [
        'last_flush_at' => null,
    ])));

    $this->withoutMockingConsoleOutput();
    $exit = $this->artisan('daywatch:status');

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('never');
});

it('outputs the raw counters with --json', function () {
    $counters = daemonCounters();
    $this->app->instance(Client::class, new StubClient(statsResult: $counters));

    $this->withoutMockingConsoleOutput();
    $exit = $this->artisan('daywatch:status', ['--json' => true]);

    expect($exit)->toBe(0)
        ->and(json_decode(Artisan::output(), true))->toBe($counters);
});

it('still reports reachability when the daemon acks a PING but has no STATS reply', function () {
    $this->app->instance(Client::class, new StubClient(pingResult: true, statsResult: null));

    $this->artisan('daywatch:status')
        ->expectsOutputToContain('reachable')
        ->assertExitCode(0);
});

it('reports the daemon unreachable and exits non-zero when it is down', function () {
    $this->app->instance(Client::class, new StubClient(pingResult: false, statsResult: null));

    $this->artisan('daywatch:status')
        ->expectsOutputToContain('daemon unreachable')
        ->assertExitCode(1);
});

it('never throws — a probe error is a non-zero exit', function () {
    $this->app->instance(Client::class, new StubClient(throw: true));

    $this->artisan('daywatch:status')->assertExitCode(1);
});

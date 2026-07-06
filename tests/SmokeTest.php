<?php

declare(strict_types=1);

use Daywatch\Agent\Daywatch;
use Daywatch\Agent\Facades\Daywatch as DaywatchFacade;
use Illuminate\Contracts\Console\Kernel;

it('boots the provider and merges the config defaults', function () {
    expect(config('daywatch.enabled'))->toBeTrue()
        ->and(config('daywatch.ingest.uri'))->toBe('127.0.0.1:2408')
        ->and(config('daywatch.ingest.event_buffer'))->toBe(500)
        ->and(config('daywatch.ingest.timeout'))->toBe(0.5)
        ->and(config('daywatch.sampling.requests'))->toBe(1.0)
        ->and(config('daywatch.sampling.exceptions'))->toBe(1.0)
        ->and(config('daywatch.agent.log_level'))->toBe('error');
});

it('resolves the Daywatch runtime as a singleton', function () {
    expect(app(Daywatch::class))->toBeInstanceOf(Daywatch::class)
        ->and(app(Daywatch::class))->toBe(app(Daywatch::class));
});

it('exposes an un-crashable fluent facade surface', function () {
    // Per the cardinal rule these must never throw into the host app, whatever
    // the daemon's state. Chaining also proves the fluent contract.
    DaywatchFacade::user('42')->sample()->dontSample()->pause()->resume()->digest();
    DaywatchFacade::report(new RuntimeException('boom'));
    DaywatchFacade::ignore(new RuntimeException('ignored'));

    expect(true)->toBeTrue();
});

it('registers the console commands', function () {
    $commands = array_keys(app(Kernel::class)->all());

    expect($commands)->toContain('daywatch:agent')
        ->toContain('daywatch:status');
});

it('contributes a Daywatch section to the about command', function () {
    $this->artisan('about', ['--only' => 'daywatch'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Daywatch')
        ->expectsOutputToContain(Daywatch::VERSION);
});

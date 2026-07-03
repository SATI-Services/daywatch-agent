<?php

declare(strict_types=1);

use Daywatch\Agent\AgentServiceProvider;
use Illuminate\Support\ServiceProvider;

it('merges the packaged config defaults', function () {
    expect(config('daywatch.enabled'))->toBeTrue()
        ->and(config('daywatch.base_url'))->toBeNull()
        ->and(config('daywatch.ingest.uri'))->toBe('127.0.0.1:2408')
        ->and(config('daywatch.ingest.event_buffer'))->toBe(500)         // records buffer size
        ->and(config('daywatch.ingest.connection_timeout'))->toBe(0.5)   // socket connect timeout
        ->and(config('daywatch.ingest.timeout'))->toBe(0.5)              // socket write/ack timeout
        ->and(config('daywatch.sampling.requests'))->toBe(1.0)
        ->and(config('daywatch.sampling.exceptions'))->toBe(1.0)
        ->and(config('daywatch.agent.flush_bytes'))->toBe(6_000_000)     // 6 MB flush ceiling
        ->and(config('daywatch.agent.flush_interval'))->toBe(10)         // 10 s flush ceiling
        ->and(config('daywatch.agent.max_concurrent_requests'))->toBe(5)
        ->and(config('daywatch.filtering.ignore_queries'))->toBeFalse();
});

it('registers the config file under the daywatch-config publish tag', function () {
    $paths = ServiceProvider::pathsToPublish(AgentServiceProvider::class, 'daywatch-config');

    expect($paths)->not->toBeEmpty();

    $source = (string) array_key_first($paths);
    expect($source)->toEndWith('config/daywatch.php')
        ->and($paths[$source])->toEndWith('config/daywatch.php');
});

it('publishes the config file with vendor:publish --tag=daywatch-config', function () {
    $target = $this->app->configPath('daywatch.php');
    @unlink($target);

    $this->artisan('vendor:publish', ['--tag' => 'daywatch-config', '--force' => true])
        ->assertExitCode(0);

    expect(file_exists($target))->toBeTrue();

    // The published file is valid PHP returning the tunables a consumer overrides.
    $published = require $target;
    expect($published)->toBeArray()
        ->toHaveKey('enabled')
        ->and($published['ingest']['event_buffer'])->toBe(500);

    @unlink($target);
});

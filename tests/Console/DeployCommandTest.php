<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['daywatch.base_url' => 'https://ingest.test', 'daywatch.token' => 'dw_tok']);
});

it('posts a deploy marker with a bearer token and the contract body', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $this->artisan('daywatch:deploy', [
        'deploy' => 'abc123',
        '--ref' => 'deadbeef',
        '--name' => 'v1.2.3',
        '--url' => 'https://rel.test/notes',
    ])->assertExitCode(0);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://ingest.test/api/deploys'
            && $request->hasHeader('Authorization', 'Bearer dw_tok')
            && $request['deploy'] === 'abc123'
            && $request['ref'] === 'deadbeef'
            && $request['name'] === 'v1.2.3'
            && $request['url'] === 'https://rel.test/notes'
            && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/', $request['timestamp']) === 1;
    });
});

it('falls back to the configured deployment marker', function () {
    config(['daywatch.deployment' => 'env-deploy']);
    Http::fake(['*' => Http::response([], 200)]);

    $this->artisan('daywatch:deploy')->assertExitCode(0);

    Http::assertSent(fn ($request) => $request['deploy'] === 'env-deploy');
});

it('fails when base_url is not configured', function () {
    config(['daywatch.base_url' => null]);
    Http::fake();

    $this->artisan('daywatch:deploy', ['deploy' => 'abc123'])->assertExitCode(1);

    Http::assertNothingSent();
});

it('fails when no deploy identifier is available', function () {
    config(['daywatch.deployment' => null]);
    Http::fake();

    $this->artisan('daywatch:deploy')->assertExitCode(1);

    Http::assertNothingSent();
});

it('fails (without throwing) on a non-2xx ingest response', function () {
    Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);

    $this->artisan('daywatch:deploy', ['deploy' => 'abc123'])->assertExitCode(1);
});

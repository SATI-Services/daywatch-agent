<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\AuthProbe;
use Daywatch\Agent\Daemon\AuthProbeResult;
use Daywatch\Agent\Daemon\DaemonStats;
use Daywatch\Agent\Daemon\HttpResponse;
use Daywatch\Agent\Support\FrozenClock;
use Daywatch\Agent\Tests\Support\FakeHttpSender;

/**
 * @param  array<int, HttpResponse|Throwable|string>  $queue
 * @return array{0: AuthProbe, 1: FakeHttpSender, 2: DaemonStats, 3: ArrayObject<int,string>}
 */
function makeProbe(array $queue, string $token = 'dw_token'): array
{
    $sender = new FakeHttpSender($queue);
    $stats = new DaemonStats('https://daywatch.test', new FrozenClock(1000.0));
    $logs = new ArrayObject;

    $probe = new AuthProbe(
        sender: $sender,
        url: 'https://daywatch.test/api/ingest',
        token: $token,
        server: 'demo-01',
        userAgent: 'DaywatchAgent/test',
        stats: $stats,
        logger: static fn (string $m) => $logs->append($m),
        batchIdFactory: static fn (): string => 'probe-batch',
    );

    return [$probe, $sender, $stats, $logs];
}

function probeResult(AuthProbe $probe): AuthProbeResult
{
    $result = null;

    $probe->run()->then(function (AuthProbeResult $r) use (&$result): void {
        $result = $r;
    });

    expect($result)->toBeInstanceOf(AuthProbeResult::class);

    return $result;
}

it('preflights the real ingest endpoint with an empty gzipped batch and batch headers', function () {
    [$probe, $sender] = makeProbe([new HttpResponse(202, '{"accepted":0}'), new HttpResponse(404)]);

    probeResult($probe);

    $sent = $sender->sent[0];

    expect($sent['method'])->toBe('POST')
        ->and($sent['url'])->toBe('https://daywatch.test/api/ingest')
        ->and(gzdecode($sent['body']))->toBe('{"records":[]}')
        ->and($sent['headers'])->toMatchArray([
            'Authorization' => 'Bearer dw_token',
            'Content-Type' => 'application/json',
            'Content-Encoding' => 'gzip',
            'Daywatch-Server' => 'demo-01',
            'Daywatch-Batch-Id' => 'probe-batch',
            'User-Agent' => 'DaywatchAgent/test',
        ]);
});

it('reports ok on a 202 and names the tenancy when the ingest can resolve it', function () {
    [$probe, $sender, $stats, $logs] = makeProbe([
        new HttpResponse(202, '{"accepted":0}'),
        new HttpResponse(200, '{"project_id":3,"environment":"production"}'),
    ]);

    $result = probeResult($probe);

    expect($result->ok())->toBeTrue()
        ->and($result->projectId)->toBe('3')
        ->and($result->environment)->toBe('production')
        ->and($sender->sent[1]['method'])->toBe('GET')
        ->and($sender->sent[1]['url'])->toBe('https://daywatch.test/api/ingest/tenancy')
        ->and($stats->authState())->toBe(AuthProbeResult::OK)
        ->and($logs[0])->toBe('[daywatch:agent] auth ok · token accepted · project 3 · production');
});

it('stays ok when the ingest exposes no tenancy endpoint (the Java relay)', function () {
    [$probe, , $stats, $logs] = makeProbe([new HttpResponse(202, '{"accepted":0}'), new HttpResponse(404, 'Not found')]);

    $result = probeResult($probe);

    expect($result->ok())->toBeTrue()
        ->and($result->projectId)->toBeNull()
        ->and($stats->authSummary())->toBe('ok · token accepted')
        ->and($logs)->toHaveCount(1);
});

it('stays ok when the tenancy lookup itself fails outright', function () {
    [$probe] = makeProbe([new HttpResponse(202), new RuntimeException('connection reset')]);

    expect(probeResult($probe)->ok())->toBeTrue();
});

it('flags a rejected token on 401 and never asks for tenancy', function () {
    [$probe, $sender, $stats, $logs] = makeProbe([new HttpResponse(401, '{"message":"Invalid ingest token."}')]);

    $result = probeResult($probe);

    expect($result->state)->toBe(AuthProbeResult::UNAUTHORIZED)
        ->and($result->isAuthFailure())->toBeTrue()
        ->and($sender->count())->toBe(1)
        ->and($stats->authSummary())->toContain('REJECTED · token not accepted (401) — check DAYWATCH_TOKEN')
        ->and($logs[1])->toContain('telemetry will be dropped');
});

it('treats 403 as a rejected token too', function () {
    expect(probeResult(makeProbe([new HttpResponse(403)])[0])->state)->toBe(AuthProbeResult::UNAUTHORIZED);
});

it('points at DAYWATCH_BASE_URL when nothing serves the ingest path', function () {
    [$probe, , $stats] = makeProbe([new HttpResponse(404, '<html>Not Found</html>')]);

    $result = probeResult($probe);

    expect($result->state)->toBe(AuthProbeResult::NOT_FOUND)
        ->and($result->isAuthFailure())->toBeTrue()
        ->and($stats->authSummary())->toBe('NOT FOUND · no ingest endpoint (404) — check DAYWATCH_BASE_URL');
});

it('reports a shedding ingest as unverified, quoting its message', function () {
    $result = probeResult(makeProbe([
        new HttpResponse(503, '{"message":"Ingest storage unavailable.","stop":true,"refresh_in":900}'),
    ])[0]);

    expect($result->state)->toBe(AuthProbeResult::UNVERIFIED)
        ->and($result->isAuthFailure())->toBeFalse()
        ->and($result->summary())->toBe('unverified · ingest answered 503 (Ingest storage unavailable.)');
});

it('resolves (never rejects) when the transport fails', function () {
    [$probe, , $stats, $logs] = makeProbe([new RuntimeException('Connection refused')]);

    $result = probeResult($probe);

    expect($result->state)->toBe(AuthProbeResult::UNREACHABLE)
        ->and($result->detail)->toBe('Connection refused')
        ->and($stats->authSummary())->toBe('UNREACHABLE · Connection refused')
        ->and($logs[0])->toContain('UNREACHABLE · Connection refused');
});

it('short-circuits with no request at all when no token is configured', function () {
    [$probe, $sender, $stats] = makeProbe([new HttpResponse(202)], token: '  ');

    $result = probeResult($probe);

    expect($sender->count())->toBe(0)
        ->and($result->state)->toBe(AuthProbeResult::MISSING_TOKEN)
        ->and($result->isAuthFailure())->toBeTrue()
        ->and($stats->authSummary())->toContain('DAYWATCH_TOKEN is empty');
});

it('leaves the STATS wire contract untouched', function () {
    [$probe, , $stats] = makeProbe([new HttpResponse(401)]);

    probeResult($probe);

    expect(array_keys($stats->toArray()))->toBe([
        'base_url', 'records_received', 'records_buffered', 'buffered_bytes',
        'batches_sent', 'records_sent', 'send_failures', 'retries',
        'last_flush_at', 'last_flush_records',
    ]);
});

it('starts out pending before the check settles', function () {
    $stats = new DaemonStats('https://daywatch.test', new FrozenClock(1000.0));

    expect($stats->authState())->toBe(AuthProbeResult::PENDING)
        ->and($stats->authSummary())->toBe('checking…');
});

it('never lets a probe failure escape', function () {
    $probe = new AuthProbe(
        sender: new FakeHttpSender([new HttpResponse(202), new HttpResponse(404)]),
        url: 'https://daywatch.test/api/ingest',
        token: 'dw_token',
        logger: static fn (string $m) => throw new RuntimeException('logger exploded'),
    );

    expect(fn () => $probe->run())->not->toThrow(Throwable::class);
});

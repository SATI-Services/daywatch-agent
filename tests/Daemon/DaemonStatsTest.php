<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\BatchBuffer;
use Daywatch\Agent\Daemon\DaemonStats;
use Daywatch\Agent\Daemon\HttpResponse;
use Daywatch\Agent\Daemon\IngestDispatcher;
use Daywatch\Agent\Daemon\IngestServer;
use Daywatch\Agent\Support\FrozenClock;
use Daywatch\Agent\Tests\Support\FakeHttpSender;
use Daywatch\Agent\Tests\Support\FakeScheduler;

/**
 * @return array{0: IngestServer, 1: DaemonStats, 2: FrozenClock, 3: FakeScheduler}
 */
function makeStatsRig(FakeHttpSender $sender, int $flushBytes = 6_000_000, int $flushInterval = 10): array
{
    $clock = new FrozenClock(1000.0);
    $stats = new DaemonStats('https://daywatch.example.com', $clock);
    $scheduler = new FakeScheduler;

    $dispatcher = new IngestDispatcher(
        sender: $sender,
        scheduler: $scheduler,
        url: 'https://daywatch.example.com/api/ingest',
        token: 'dw',
        server: 'srv',
        userAgent: 'UA/test',
        stats: $stats,
    );

    $server = new IngestServer(new BatchBuffer($clock, $flushBytes, $flushInterval), $dispatcher, $stats);

    return [$server, $stats, $clock, $scheduler];
}

it('tracks received and buffered records through digests', function () {
    [$server, $stats] = makeStatsRig(new FakeHttpSender([new HttpResponse(202)]));

    $server->ingest('[{"a":1},{"b":2}]');
    $server->ingest('[{"c":3}]');

    expect($stats->toArray())->toMatchArray([
        'base_url' => 'https://daywatch.example.com',
        'records_received' => 3,
        'records_buffered' => 3,
        'buffered_bytes' => strlen('{"a":1},{"b":2},{"c":3}'),
        'batches_sent' => 0,
        'records_sent' => 0,
        'send_failures' => 0,
        'retries' => 0,
        'last_flush_at' => null,
        'last_flush_records' => 0,
    ]);
});

it('moves buffered counts to sent counts across a full digest → flush → 2xx cycle', function () {
    [$server, $stats, $clock] = makeStatsRig(new FakeHttpSender([new HttpResponse(202)]));

    $server->ingest('[{"a":1},{"b":2}]');
    $server->ingest('[{"c":3}]');

    $clock->advance(10.0);
    $server->tick(); // age flush → dispatch → 202 (resolves synchronously)

    expect($stats->toArray())->toMatchArray([
        'records_received' => 3,
        'records_buffered' => 0,
        'buffered_bytes' => 0,
        'batches_sent' => 1,
        'records_sent' => 3,
        'send_failures' => 0,
        'retries' => 0,
        'last_flush_at' => 1010.0,
        'last_flush_records' => 3,
    ]);
});

it('ignores malformed (non-array) digests in the counters', function () {
    [$server, $stats] = makeStatsRig(new FakeHttpSender);

    $server->ingest('not an array');
    $server->ingest('{"t":"query"}');

    expect($stats->toArray())->toMatchArray([
        'records_received' => 0,
        'records_buffered' => 0,
        'buffered_bytes' => 0,
    ]);
});

it('counts a failed POST and its scheduled retry, then the successful redelivery', function () {
    $sender = new FakeHttpSender([new RuntimeException('refused'), new HttpResponse(202)]);
    [$server, $stats, , $scheduler] = makeStatsRig($sender);

    $server->ingest('[{"a":1},{"b":2}]');
    $server->finalDigest(); // dispatch → network error → retained on the ladder

    expect($stats->toArray())->toMatchArray([
        'records_received' => 2,
        'records_buffered' => 0,
        'batches_sent' => 0,
        'records_sent' => 0,
        'send_failures' => 1,
        'retries' => 1,
        'last_flush_records' => 2,
    ]);

    $scheduler->fireNext(); // ladder retry → 202

    expect($stats->toArray())->toMatchArray([
        'batches_sent' => 1,
        'records_sent' => 2,
        'send_failures' => 1,
        'retries' => 1,
    ]);
});

it('counts a non-2xx response as a send failure', function () {
    $sender = new FakeHttpSender([new HttpResponse(422, '{"message":"nope"}')]);
    [$server, $stats] = makeStatsRig($sender);

    $server->ingest('[{"a":1}]');
    $server->finalDigest(); // 422 poison → dropped

    expect($stats->toArray())->toMatchArray([
        'batches_sent' => 0,
        'records_sent' => 0,
        'send_failures' => 1,
        'retries' => 0, // poison batches are dropped, not retried
    ]);
});

it('exposes the exact STATS reply field names (wire contract)', function () {
    $stats = new DaemonStats('https://daywatch.example.com', new FrozenClock(1.0));

    expect(array_keys($stats->toArray()))->toBe([
        'base_url',
        'records_received',
        'records_buffered',
        'buffered_bytes',
        'batches_sent',
        'records_sent',
        'send_failures',
        'retries',
        'last_flush_at',
        'last_flush_records',
    ])->and(json_decode($stats->toJson(), true))->toBe($stats->toArray());
});

it('formats the periodic log line with a last-flush age', function () {
    [$server, $stats, $clock] = makeStatsRig(new FakeHttpSender([new HttpResponse(202)]));

    expect($stats->toLogLine())->toContain('stats target=https://daywatch.example.com')
        ->toContain('received=0')
        ->toContain('last_flush=never');

    $server->ingest('[{"a":1},{"b":2}]');
    $clock->advance(10.0);
    $server->tick();
    $clock->advance(3.0);

    expect($stats->toLogLine())->toBe(
        '[daywatch:agent] stats target=https://daywatch.example.com '
        .'received=2 buffered=0 buffered_bytes=0 batches=1 sent=2 failures=0 retries=0 '
        .'last_flush=3s last_flush_records=2',
    );
});

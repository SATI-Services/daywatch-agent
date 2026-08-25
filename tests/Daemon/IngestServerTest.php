<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\BatchBuffer;
use Daywatch\Agent\Daemon\HttpResponse;
use Daywatch\Agent\Daemon\IngestDispatcher;
use Daywatch\Agent\Daemon\IngestServer;
use Daywatch\Agent\Tests\Support\FakeHttpSender;
use Daywatch\Agent\Tests\Support\FakeScheduler;
use Daywatch\Agent\Tests\Support\FrozenClock;

/** @return array{0: IngestServer, 1: BatchBuffer} */
function makeIngestServer(FakeHttpSender $sender, FrozenClock $clock, int $flushBytes = 6_000_000, int $flushInterval = 10): array
{
    $buffer = new BatchBuffer($clock, $flushBytes, $flushInterval);
    $dispatcher = new IngestDispatcher(
        $sender, new FakeScheduler, 'https://ingest.test/api/ingest', 'dw', 'srv', 'UA/test',
    );

    return [new IngestServer($buffer, $dispatcher), $buffer];
}

it('buffers digests below the flush ceiling without sending', function () {
    $sender = new FakeHttpSender([new HttpResponse(202)]);
    [$server] = makeIngestServer($sender, new FrozenClock(1.0));

    $server->ingest('[{"a":1}]');

    expect($sender->count())->toBe(0);
});

it('flushes and POSTs once the size ceiling is crossed', function () {
    $sender = new FakeHttpSender([new HttpResponse(202)]);
    [$server] = makeIngestServer($sender, new FrozenClock(1.0), flushBytes: 5);

    $server->ingest('[{"aaaa":1}]');

    expect($sender->count())->toBe(1)
        ->and(gzdecode($sender->last()['body']))->toBe('{"records":[{"aaaa":1}]}');
});

it('flushes on the age ceiling via tick', function () {
    $clock = new FrozenClock(1000.0);
    $sender = new FakeHttpSender([new HttpResponse(202)]);
    [$server] = makeIngestServer($sender, $clock, flushBytes: 6_000_000, flushInterval: 10);

    $server->ingest('[{"a":1}]');
    $server->tick();
    expect($sender->count())->toBe(0);

    $clock->advance(10.0);
    $server->tick();
    expect($sender->count())->toBe(1);
});

it('pre-flushes when an incoming digest would exceed the ceiling', function () {
    $sender = new FakeHttpSender([new HttpResponse(202), new HttpResponse(202)]);
    [$server] = makeIngestServer($sender, new FrozenClock(1.0), flushBytes: 20);

    $server->ingest('[{"a":1}]');           // 7 record bytes — buffered
    expect($sender->count())->toBe(0);

    $server->ingest('[{"bbbbbbbb":2}]');    // 14 more; 7+14 ≥ 20 → pre-flush the first

    expect($sender->count())->toBe(1)
        ->and(gzdecode($sender->last()['body']))->toBe('{"records":[{"a":1}]}');
});

it('flushes the remainder on final digest', function () {
    $sender = new FakeHttpSender([new HttpResponse(202)]);
    [$server] = makeIngestServer($sender, new FrozenClock(1.0));

    $server->ingest('[{"a":1}]');
    $server->finalDigest();

    expect($sender->count())->toBe(1);
});

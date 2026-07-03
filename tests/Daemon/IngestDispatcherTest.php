<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\HttpResponse;
use Daywatch\Agent\Daemon\IngestDispatcher;
use Daywatch\Agent\Tests\Support\FakeHttpSender;
use Daywatch\Agent\Tests\Support\FakeScheduler;

function makeDispatcher(
    FakeHttpSender $sender,
    FakeScheduler $scheduler,
    array &$logs,
    ?callable $batchId = null,
    int $maxConcurrent = 5,
    int $retainCap = 33_554_432,
): IngestDispatcher {
    return new IngestDispatcher(
        sender: $sender,
        scheduler: $scheduler,
        url: 'https://ingest.test/api/ingest',
        token: 'dw_token',
        server: 'demo-01',
        userAgent: 'DaywatchAgent/test',
        maxConcurrent: $maxConcurrent,
        retainCapBytes: $retainCap,
        logger: function (string $m) use (&$logs): void {
            $logs[] = $m;
        },
        batchIdFactory: $batchId,
    );
}

it('gzips and POSTs a batch with the contract headers, then discards on 202', function () {
    $sender = new FakeHttpSender([new HttpResponse(202, '{"accepted":1}')]);
    $scheduler = new FakeScheduler;
    $logs = [];

    makeDispatcher($sender, $scheduler, $logs, fn () => 'batch-xyz')
        ->dispatch('{"records":[{"a":1}]}');

    expect($sender->count())->toBe(1)
        ->and($scheduler->pending())->toBe(0);

    $sent = $sender->last();
    expect($sent['method'])->toBe('POST')
        ->and($sent['url'])->toBe('https://ingest.test/api/ingest')
        ->and(gzdecode($sent['body']))->toBe('{"records":[{"a":1}]}')
        ->and($sent['headers']['Authorization'])->toBe('Bearer dw_token')
        ->and($sent['headers']['Content-Encoding'])->toBe('gzip')
        ->and($sent['headers']['Content-Type'])->toBe('application/json')
        ->and($sent['headers']['Daywatch-Server'])->toBe('demo-01')
        ->and($sent['headers']['Daywatch-Batch-Id'])->toBe('batch-xyz')
        ->and($sent['headers']['User-Agent'])->toBe('DaywatchAgent/test');
});

it('drops digests beyond the max concurrent in-flight cap', function () {
    $sender = new FakeHttpSender([], 'pending'); // never settle → slots stay held
    $scheduler = new FakeScheduler;
    $logs = [];
    $dispatcher = makeDispatcher($sender, $scheduler, $logs);

    for ($i = 0; $i < 6; $i++) {
        $dispatcher->dispatch('{"records":[{"n":'.$i.'}]}');
    }

    expect($sender->count())->toBe(5)
        ->and($dispatcher->inFlight())->toBe(5)
        ->and(implode("\n", $logs))->toContain('max concurrent');
});

it('retries a 429 after retry_in, reusing the same batch id', function () {
    $n = 0;
    $sender = new FakeHttpSender([new HttpResponse(429, '{"retry_in":7}'), new HttpResponse(202)]);
    $scheduler = new FakeScheduler;
    $logs = [];

    makeDispatcher($sender, $scheduler, $logs, function () use (&$n) {
        return 'batch-'.$n++;
    })->dispatch('{"records":[{"a":1}]}');

    expect($sender->count())->toBe(1)
        ->and($scheduler->delays())->toBe([7.0]);

    $scheduler->fireNext();

    expect($sender->count())->toBe(2)
        ->and($sender->sent[0]['headers']['Daywatch-Batch-Id'])->toBe('batch-0')
        ->and($sender->sent[1]['headers']['Daywatch-Batch-Id'])->toBe('batch-0');
});

it('walks the retry ladder on repeated 5xx', function () {
    $sender = new FakeHttpSender([new HttpResponse(500), new HttpResponse(500), new HttpResponse(202)]);
    $scheduler = new FakeScheduler;
    $logs = [];
    $dispatcher = makeDispatcher($sender, $scheduler, $logs);

    $dispatcher->dispatch('{"records":[{"a":1}]}');
    expect($scheduler->delays())->toBe([2.5]);   // 1st failure

    $scheduler->fireNext();
    expect($scheduler->delays())->toBe([5.0]);   // 2nd failure escalates

    $scheduler->fireNext();
    expect($sender->count())->toBe(3)
        ->and($scheduler->pending())->toBe(0);   // 202 clears the ladder
});

it('retries a network error on the ladder', function () {
    $sender = new FakeHttpSender([new RuntimeException('connection refused'), new HttpResponse(202)]);
    $scheduler = new FakeScheduler;
    $logs = [];
    $dispatcher = makeDispatcher($sender, $scheduler, $logs);

    $dispatcher->dispatch('{"records":[{"a":1}]}');

    expect($scheduler->delays())->toBe([2.5])
        ->and(implode("\n", $logs))->toContain('network error');

    $scheduler->fireNext();
    expect($sender->count())->toBe(2);
});

it('honours the 503 stop pause contract (NullBuffer, then re-probe)', function () {
    $sender = new FakeHttpSender([new HttpResponse(503, '{"stop":true,"refresh_in":900}')]);
    $scheduler = new FakeScheduler;
    $logs = [];
    $dispatcher = makeDispatcher($sender, $scheduler, $logs);

    $dispatcher->dispatch('{"records":[{"a":1}]}');

    expect($dispatcher->isPaused())->toBeTrue()
        ->and($scheduler->delays())->toBe([900.0]);

    // While paused, every incoming batch is dropped (NullBuffer).
    $dispatcher->dispatch('{"records":[{"b":2}]}');
    expect($sender->count())->toBe(1)
        ->and(implode("\n", $logs))->toContain('paused');

    // The re-probe window elapsing resumes attempts.
    $scheduler->fireNext();
    expect($dispatcher->isPaused())->toBeFalse();
});

it('defaults the pause window to 900s when refresh_in is omitted', function () {
    $sender = new FakeHttpSender([new HttpResponse(503, '{"stop":true}')]);
    $scheduler = new FakeScheduler;
    $logs = [];

    makeDispatcher($sender, $scheduler, $logs)->dispatch('{"records":[{"a":1}]}');

    expect($scheduler->delays())->toBe([IngestDispatcher::DEFAULT_REFRESH_IN]);
});

it('drops a 401 batch without retrying', function () {
    $sender = new FakeHttpSender([new HttpResponse(401, '{"message":"bad token"}')]);
    $scheduler = new FakeScheduler;
    $logs = [];
    $dispatcher = makeDispatcher($sender, $scheduler, $logs);

    $dispatcher->dispatch('{"records":[{"a":1}]}');

    expect($scheduler->pending())->toBe(0)
        ->and($dispatcher->inFlight())->toBe(0)
        ->and(implode("\n", $logs))->toContain('401');
});

it('drops poison 413/422 batches without retrying', function (int $status) {
    $sender = new FakeHttpSender([new HttpResponse($status, '{"message":"nope"}')]);
    $scheduler = new FakeScheduler;
    $logs = [];

    makeDispatcher($sender, $scheduler, $logs)->dispatch('{"records":[{"a":1}]}');

    expect($scheduler->pending())->toBe(0);
})->with([413, 422]);

it('drops a single batch that exceeds the retry buffer cap', function () {
    $sender = new FakeHttpSender([], new HttpResponse(500));
    $scheduler = new FakeScheduler;
    $logs = [];
    $dispatcher = makeDispatcher($sender, $scheduler, $logs, retainCap: 1);

    $dispatcher->dispatch('{"records":[{"a":1}]}');

    expect($dispatcher->retainedBytes())->toBe(0)
        ->and(implode("\n", $logs))->toContain('single batch exceeds');
});

it('evicts the oldest retained batch when the retry buffer is full', function () {
    $body = '{"records":[{"n":1}]}';
    $one = strlen(gzencode($body, 6));

    $sender = new FakeHttpSender([], new HttpResponse(500)); // every attempt fails → retained
    $scheduler = new FakeScheduler;
    $logs = [];
    $dispatcher = makeDispatcher($sender, $scheduler, $logs, retainCap: $one);

    $dispatcher->dispatch($body);
    $dispatcher->dispatch($body);

    expect($dispatcher->retainedBytes())->toBe($one)
        ->and(implode("\n", $logs))->toContain('dropped oldest');
});

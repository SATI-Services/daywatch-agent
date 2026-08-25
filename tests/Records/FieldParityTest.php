<?php

declare(strict_types=1);

use Daywatch\Agent\Records\CacheEventRecord;
use Daywatch\Agent\Records\CommandRecord;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\ExceptionRecord;
use Daywatch\Agent\Records\JobAttemptRecord;
use Daywatch\Agent\Records\LogRecord;
use Daywatch\Agent\Records\MailRecord;
use Daywatch\Agent\Records\NotificationRecord;
use Daywatch\Agent\Records\OutgoingRequestRecord;
use Daywatch\Agent\Records\QueryRecord;
use Daywatch\Agent\Records\QueuedJobRecord;
use Daywatch\Agent\Records\ScheduledTaskRecord;
use Daywatch\Agent\Records\UserRecord;

/** Walk up from the package to find the shared wire-contract sample, if present. */
function sampleRecords(): ?array
{
    $dir = __DIR__;

    for ($i = 0; $i < 6; $i++) {
        // The sample lives in the daywatch-mcp system docs (sibling checkout:
        // ../daywatch-mcp/docs/samples/); a plain docs/samples/ copy is
        // also honoured for standalone checkouts.
        foreach (['/daywatch-mcp/docs/samples/records.v1.json', '/docs/samples/records.v1.json'] as $relative) {
            $candidate = $dir.$relative;

            if (is_file($candidate)) {
                return json_decode((string) file_get_contents($candidate), true)['records'] ?? null;
            }
        }

        $dir = dirname($dir);
    }

    return null;
}

function sampleKeysFor(string $type): array
{
    $records = sampleRecords();

    if ($records === null) {
        return [];
    }

    foreach ($records as $record) {
        if (($record['t'] ?? null) === $type) {
            return array_keys($record);
        }
    }

    return [];
}

it('emits request fields in the exact order of the canonical sample', function () {
    $expected = sampleKeysFor('request');

    if ($expected === []) {
        $this->markTestSkipped('daywatch-mcp/docs/samples/records.v1.json not reachable from this checkout');
    }

    $keys = array_keys(makeRequestRecord()->toArray());

    expect($keys)->toBe($expected);
});

it('emits query fields in the exact order of the canonical sample', function () {
    $expected = sampleKeysFor('query');

    if ($expected === []) {
        $this->markTestSkipped('sample not reachable');
    }

    $record = new QueryRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        sql: 'select 1', file: '', line: 0, duration: 0, connection: 'mysql', connectionType: 'read',
    );

    expect(array_keys($record->toArray()))->toBe($expected);
});

it('emits exception fields in the exact order of the canonical sample', function () {
    $expected = sampleKeysFor('exception');

    if ($expected === []) {
        $this->markTestSkipped('sample not reachable');
    }

    $record = new ExceptionRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        class: 'X', file: 'f', line: 1, message: 'm', code: '0', trace: '[]',
        handled: true, phpVersion: '8.2', laravelVersion: '11',
    );

    expect(array_keys($record->toArray()))->toBe($expected);
});

/**
 * Every new record type built with a minimal fixture, keyed by `t`. The exact
 * emitted key order must match the canonical sample batch (wire contract).
 */
dataset('new_records', [
    'cache-event' => [fn () => new CacheEventRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        store: 'redis', key: 'k', type: 'miss', duration: 0, ttl: 0,
    )],
    'outgoing-request' => [fn () => new OutgoingRequestRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        host: 'h', method: 'GET', url: 'u', duration: 0, requestSize: 0, responseSize: 0, statusCode: 200,
    )],
    'log' => [fn () => new LogRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        level: 'info', message: 'm', context: '{}', extra: '{}',
    )],
    'queued-job' => [fn () => new QueuedJobRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        jobId: 'j', name: 'n', connection: 'c', queue: 'q', duration: 0,
    )],
    'job-attempt' => [fn () => new JobAttemptRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't'),
        jobId: 'j', attemptId: 'a', attempt: 1, name: 'n', connection: 'c', queue: 'q',
        status: 'processed', duration: 0, counters: [], peakMemoryUsage: 0, exceptionPreview: '', context: '{}',
    )],
    'command' => [fn () => new CommandRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't'),
        class: 'C', name: 'n', command: 'n', exitCode: 0, stages: [], counters: [],
        peakMemoryUsage: 0, exceptionPreview: '', context: '{}',
    )],
    'scheduled-task' => [fn () => new ScheduledTaskRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't'),
        name: 'n', cron: '* * * * *', timezone: 'UTC', withoutOverlapping: true, onOneServer: false,
        runInBackground: false, status: 'processed', duration: 0, counters: [], peakMemoryUsage: 0,
        exceptionPreview: '', context: '{}',
    )],
    'mail' => [fn () => new MailRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        mailer: 'smtp', class: 'M', subject: 's', to: 1, cc: 0, bcc: 0, attachments: 0, duration: 0, failed: false,
    )],
    'notification' => [fn () => new NotificationRecord(
        envelope: new Envelope(timestamp: 0.0, traceId: 't', executionId: 'e', executionSource: 'request', executionStage: 'action'),
        channel: 'mail', class: 'N', duration: 0, failed: false,
    )],
    'user' => [fn () => new UserRecord(
        envelope: new Envelope(timestamp: 0.0), id: '42', name: 'n', username: 'u',
    )],
]);

it('emits new-record fields in the exact order of the canonical sample', function (callable $make) {
    $record = $make();
    $type = $record->toArray()['t'];
    $expected = sampleKeysFor($type);

    if ($expected === []) {
        $this->markTestSkipped("sample for {$type} not reachable");
    }

    expect(array_keys($record->toArray()))->toBe($expected);
})->with('new_records');

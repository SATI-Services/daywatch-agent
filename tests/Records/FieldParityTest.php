<?php

declare(strict_types=1);

use Daywatch\Agent\Records\ExceptionRecord;
use Daywatch\Agent\Records\QueryRecord;

/** Walk up from the package to find the shared wire-contract sample, if present. */
function sampleRecords(): ?array
{
    $dir = __DIR__;

    for ($i = 0; $i < 6; $i++) {
        // The sample lives in the daywatch-mcp system docs (in the monorepo:
        // services/daywatch-mcp/docs/samples/); a plain docs/samples/ copy is
        // also honoured for standalone checkouts.
        foreach (['/services/daywatch-mcp/docs/samples/records.v1.json', '/docs/samples/records.v1.json'] as $relative) {
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
        $this->markTestSkipped('services/daywatch-mcp/docs/samples/records.v1.json not reachable from this checkout');
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
        timestamp: 0.0, deploy: '', server: '', traceId: 't', executionId: 'e',
        executionSource: 'request', executionPreview: '', executionStage: 'action', user: '',
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
        timestamp: 0.0, deploy: '', server: '', traceId: 't', executionId: 'e',
        executionSource: 'request', executionPreview: '', executionStage: 'action', user: '',
        class: 'X', file: 'f', line: 1, message: 'm', code: '0', trace: '[]',
        handled: true, phpVersion: '8.2', laravelVersion: '11',
    );

    expect(array_keys($record->toArray()))->toBe($expected);
});

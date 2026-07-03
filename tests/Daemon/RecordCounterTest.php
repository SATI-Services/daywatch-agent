<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\RecordCounter;

it('counts the records in a flat digest payload', function () {
    expect(RecordCounter::count('[{"a":1},{"b":2},{"c":3}]'))->toBe(3);
});

it('counts an empty array as zero', function () {
    expect(RecordCounter::count('[]'))->toBe(0)
        ->and(RecordCounter::count(''))->toBe(0);
});

it('does not count nested objects or objects inside nested arrays', function () {
    // An exception record's `trace` is a real JSON array of objects — only the
    // two record roots may count.
    $payload = json_encode([
        ['t' => 'exception', 'trace' => [['file' => 'a.php:1'], ['file' => 'b.php:2', 'code' => ['3' => 'x']]]],
        ['t' => 'query', 'sql' => 'select 1'],
    ]);

    expect(RecordCounter::count($payload))->toBe(2);
});

it('ignores braces, brackets and commas inside JSON strings', function () {
    $payload = json_encode([
        ['sql' => 'select "}{" , [ { from t where x = \'{{\''],
        ['message' => 'It said: "boom" }{['],
    ]);

    expect(RecordCounter::count($payload))->toBe(2);
});

it('honours escaped quotes and escaped backslashes in strings', function () {
    $payload = json_encode([
        ['path' => 'C:\\temp\\'],            // trailing escaped backslash before the closing quote
        ['note' => 'a \\" tricky \\\\" mix'], // literal backslash-quote runs
        ['plain' => 'ok'],
    ]);

    expect(RecordCounter::count($payload))->toBe(3);
});

it('counts a single-record digest', function () {
    expect(RecordCounter::count('[{"t":"request","route_methods":["GET","HEAD"]}]'))->toBe(1);
});

it('never throws on garbage', function (string $garbage) {
    expect(RecordCounter::count($garbage))->toBeGreaterThanOrEqual(0);
})->with([
    'not json' => 'total garbage',
    'unterminated string' => '[{"a":"unterminated]',
    'unbalanced braces' => '[{{{',
    'stray closers' => '}}}]',
    'binary' => "\x00\x01\xff{\"a\":1}",
]);

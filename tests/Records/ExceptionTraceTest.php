<?php

declare(strict_types=1);

use Daywatch\Agent\Records\ExceptionTrace;
use Daywatch\Agent\Support\Location;

afterEach(fn () => Location::setBasePath(null));

it('builds a JSON array of frames with file/source/code keys', function () {
    Location::setBasePath('/nowhere-real');

    $trace = json_decode(ExceptionTrace::build(new RuntimeException('boom'), false), true);

    expect($trace)->toBeArray()->not->toBeEmpty();

    foreach ($trace as $frame) {
        expect($frame)->toHaveKeys(['file', 'source', 'code']);
    }
});

it('never inlines source when capture is disabled', function () {
    Location::setBasePath(dirname(__DIR__, 2));

    $trace = json_decode(ExceptionTrace::build(new RuntimeException('boom'), false), true);

    foreach ($trace as $frame) {
        expect($frame['code'])->toBeNull();
    }
});

it('inlines app-file source when capture is enabled', function () {
    Location::setBasePath(dirname(__DIR__, 2)); // package root — the test file is an "app" file

    $trace = json_decode(ExceptionTrace::build(new RuntimeException('boom'), true), true);

    $withSource = array_filter($trace, fn (array $f) => $f['code'] !== null);

    expect($withSource)->not->toBeEmpty();

    $first = array_values($withSource)[0];
    expect($first['code'])->toBeArray();

    foreach ($first['code'] as $lineNo => $src) {
        expect($lineNo)->toBeInt()->and($src)->toBeString();
    }
});

it('does not inline source for non-app (framework/vendor) files', function () {
    Location::setBasePath('/nowhere-real'); // nothing is app-relative → no source

    $trace = json_decode(ExceptionTrace::build(new RuntimeException('boom'), true), true);

    foreach ($trace as $frame) {
        expect($frame['code'])->toBeNull();
    }
});

it('returns a valid JSON string even for a trace-less throwable', function () {
    Location::setBasePath('/nowhere-real');

    $json = ExceptionTrace::build(new RuntimeException('boom'), true);

    expect(json_decode($json, true))->toBeArray();
});

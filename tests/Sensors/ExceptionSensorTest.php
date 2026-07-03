<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\ExceptionSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Location;
use Daywatch\Agent\Tests\Support\RecordingClient;
use Illuminate\View\ViewException;

beforeEach(fn () => Location::setBasePath('/nowhere-real'));
afterEach(fn () => Location::setBasePath(null));

it('records an exception with the mapped wire fields', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $e = new RuntimeException('kaboom', 7);
    (new ExceptionSensor($core))->report($e, handled: true);

    $record = $buffer->all()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('exception')
        ->and($record['class'])->toBe(RuntimeException::class)
        ->and($record['message'])->toBe('kaboom')
        ->and($record['code'])->toBe('7')
        ->and($record['handled'])->toBeTrue()
        ->and($record['php_version'])->toBe(PHP_VERSION)
        ->and($record['file'])->toBe(Location::appRelative($e->getFile()))
        ->and($record['line'])->toBe($e->getLine())
        ->and($record['_group'])->toBe(
            Group::exception(RuntimeException::class, '7', Location::appRelative($e->getFile()), $e->getLine())
        )
        ->and($record['trace'])->toBeJson()
        ->and($record['execution_source'])->toBe('request')
        ->and($record['trace_id'])->toBe($core->traceId);
});

it('increments the exceptions counter and sets the preview', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new ExceptionSensor($core))->report(new RuntimeException('boom'));

    expect($core->counters()['exceptions'])->toBe(1)
        ->and($core->exceptionPreview())->toBe(RuntimeException::class.': boom');
});

it('does not record an ignored exception', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $e = new RuntimeException('shh');
    $core->markIgnored($e);

    (new ExceptionSensor($core))->report($e);

    expect($buffer->all())->toBe([]);
});

it('unwraps a ViewException to its underlying cause', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $cause = new RuntimeException('root cause', 3);
    $view = new ViewException('wrapper', 0, E_ERROR, 'view.blade.php', 1, $cause);

    (new ExceptionSensor($core))->report($view);

    $record = $buffer->all()[0];

    expect($record['class'])->toBe(RuntimeException::class)
        ->and($record['message'])->toBe('root cause')
        ->and($record['code'])->toBe('3');
});

it('re-rolls sampling so a reported error escapes a sampled-out trace', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 0.0, exceptionRate: 1.0);
    $core->prepareForRequest();

    expect($core->shouldSample())->toBeFalse();

    (new ExceptionSensor($core))->report(new RuntimeException('boom'));

    expect($core->shouldSample())->toBeTrue();
});

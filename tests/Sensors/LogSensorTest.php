<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\LogSensor;
use Daywatch\Agent\Tests\Support\RecordingClient;

function fakeLogEvent(string $level = 'warning', string $message = 'msg', array $context = ['k' => 'v']): object
{
    return new class($level, $message, $context)
    {
        public function __construct(
            public string $level,
            public string $message,
            public array $context,
        ) {}
    };
}

it('maps a MessageLogged event to a log record', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new LogSensor($core))->handle(fakeLogEvent('warning', 'something happened', ['k' => 'v']));

    $record = $buffer->all()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('log')
        ->and($record['level'])->toBe('warning')
        ->and($record['message'])->toBe('something happened')
        ->and($record['context'])->toBe('{"k":"v"}')
        ->and($record['extra'])->toBe('{}')
        ->and($record)->not->toHaveKey('_group')
        ->and($record['trace_id'])->toBe($core->traceId)
        ->and($record['execution_source'])->toBe('request');
});

it('increments the logs counter', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new LogSensor($core))->handle(fakeLogEvent());

    expect($core->counters()['logs'])->toBe(1);
});

it('defaults context to {} when the event carries no array context', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new LogSensor($core))->handle(fakeLogEvent('info', 'no ctx', []));

    expect($buffer->all()[0]['context'])->toBe('{}');
});

it('never throws when the event is missing fields', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $bare = new class
    {
        // no level/message/context properties
    };

    (new LogSensor($core))->handle($bare);

    expect($buffer->all()[0]['level'])->toBe('')
        ->and($buffer->all()[0]['message'])->toBe('')
        ->and($buffer->all()[0]['context'])->toBe('{}');
});

<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\NotificationSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;

function fakeNotification(): object
{
    return new class
    {
        public string $marker = 'test-notification';
    };
}

function fakeNotifiable(): object
{
    return new class {};
}

function fakeSendingEvent(object $notification, string $channel = 'mail'): object
{
    return new class($notification, $channel, fakeNotifiable())
    {
        public function __construct(
            public object $notification,
            public string $channel,
            public object $notifiable,
        ) {}
    };
}

function fakeSentEvent(object $notification, string $channel = 'mail'): object
{
    return fakeSendingEvent($notification, $channel);
}

it('maps a NotificationSent event to a notification record', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $notification = fakeNotification();

    $sensor = new NotificationSensor($core);
    $sensor->sent(fakeSentEvent($notification, 'mail'));

    $record = $buffer->all()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('notification')
        ->and($record['channel'])->toBe('mail')
        ->and($record['class'])->toBe($notification::class)
        ->and($record['failed'])->toBe(false)
        ->and($record['_group'])->toBe(Group::name($notification::class))
        ->and($record['trace_id'])->toBe($core->traceId)
        ->and($record['execution_source'])->toBe('request');
});

it('increments the notifications counter', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new NotificationSensor($core))->sent(fakeSentEvent(fakeNotification()));

    expect($core->counters()['notifications'])->toBe(1);
});

it('pairs Sending and Sent for a non-negative duration', function () {
    $client = new RecordingClient;
    [$core, $buffer, $clock] = makeCore($client, requestRate: 1.0, now: 1000.0);
    $core->prepareForRequest();

    $notification = fakeNotification();
    $sensor = new NotificationSensor($core);

    $sensor->sending(fakeSendingEvent($notification));
    $clock->set(1000.5);
    $sensor->sent(fakeSentEvent($notification));

    $record = $buffer->all()[0];

    expect($record['duration'])->toBeGreaterThanOrEqual(0)
        ->and($record['duration'])->toBe(500_000)
        ->and($record['timestamp'])->toEqualWithDelta(1000.0, 0.0000001);
});

it('records a zero duration for an unpaired Sent event', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new NotificationSensor($core))->sent(fakeSentEvent(fakeNotification()));

    expect($buffer->all()[0]['duration'])->toBe(0);
});

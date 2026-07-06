<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\MailSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;
use Symfony\Component\Mime\Email;

function fakeMailEmail(): Email
{
    return (new Email)
        ->to('a@b.c')
        ->cc('c@d.e')
        ->subject('Hi');
}

function sendingEvent(object $message, array $data = []): object
{
    return new class($message, $data)
    {
        public function __construct(public object $message, public array $data) {}
    };
}

function sentEvent(object $message, array $data = []): object
{
    return new class($message, $data)
    {
        public bool $sent = true;

        public function __construct(public object $message, public array $data) {}
    };
}

it('maps a MessageSending → MessageSent pair to a mail record', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $message = fakeMailEmail();
    $data = ['mailer' => 'smtp', '__laravel_mailable' => 'App\\Mail\\OrderShipped'];

    $sensor = new MailSensor($core);
    $sensor->sending(sendingEvent($message, $data));
    $sensor->sent(sentEvent($message, $data));

    $record = $buffer->all()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('mail')
        ->and($record['mailer'])->toBe('smtp')
        ->and($record['class'])->toBe('App\\Mail\\OrderShipped')
        ->and($record['subject'])->toBe('Hi')
        ->and($record['to'])->toBe(1)
        ->and($record['cc'])->toBe(1)
        ->and($record['bcc'])->toBe(0)
        ->and($record['attachments'])->toBe(0)
        ->and($record['failed'])->toBeFalse()
        ->and($record['duration'])->toBeGreaterThanOrEqual(0)
        ->and($record['_group'])->toBe(Group::name('App\\Mail\\OrderShipped'))
        ->and($record['trace_id'])->toBe($core->traceId)
        ->and($record['execution_source'])->toBe('request');
});

it('computes a non-negative duration between Sending and Sent', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $message = fakeMailEmail();

    $sensor = new MailSensor($core);
    $sensor->sending(sendingEvent($message));
    $sensor->sent(sentEvent($message));

    expect($buffer->all()[0]['duration'])->toBeInt()
        ->toBeGreaterThanOrEqual(0);
});

it('increments the mail counter', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $message = fakeMailEmail();

    $sensor = new MailSensor($core);
    $sensor->sending(sendingEvent($message));
    $sensor->sent(sentEvent($message));

    expect($core->counters()['mail'])->toBe(1);
});

it('records with empty mailer/class when data is absent', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $message = fakeMailEmail();

    $sensor = new MailSensor($core);
    $sensor->sending(sendingEvent($message));
    $sensor->sent(sentEvent($message));

    $record = $buffer->all()[0];

    expect($record['mailer'])->toBe('')
        ->and($record['class'])->toBe('');
});

it('records duration 0 for an unpaired Sent (no matching Sending)', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new MailSensor($core);
    $sensor->sent(sentEvent(fakeMailEmail()));

    expect($buffer->all()[0]['duration'])->toBe(0);
});

it('never throws when the event has no message', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $sensor = new MailSensor($core);
    $sensor->sending(new class
    {
        public mixed $message = null;
    });
    $sensor->sent(new class
    {
        public mixed $message = null;
    });

    expect($buffer->all())->toBe([]);
});

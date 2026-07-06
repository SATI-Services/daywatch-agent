<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\QueuedJobSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;

/**
 * A fake JobQueueing/JobQueued event. The real events expose `$connectionName`,
 * `$queue`, `$job`, and (sometimes) a `payload()` method — we only need that
 * shape here.
 */
function fakeQueueEvent(
    string $connectionName = 'redis',
    string $queue = 'default',
    mixed $job = null,
    ?array $payload = null,
): object {
    return new class($connectionName, $queue, $job, $payload)
    {
        public function __construct(
            public string $connectionName,
            public string $queue,
            public mixed $job,
            private ?array $payloadData,
        ) {}

        public function payload(): array
        {
            return $this->payloadData ?? [];
        }
    };
}

/** A job object exposing displayName() like a Laravel queued job. */
function fakeJob(string $displayName): object
{
    return new class($displayName)
    {
        public function __construct(private string $displayName) {}

        public function displayName(): string
        {
            return $this->displayName;
        }
    };
}

it('maps a paired JobQueueing/JobQueued to a queued-job record', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $event = fakeQueueEvent(
        connectionName: 'redis',
        queue: 'emails',
        job: fakeJob('App\\Jobs\\SendWelcomeEmail'),
        payload: ['daywatch' => ['job_id' => 'job-123']],
    );

    $sensor = new QueuedJobSensor($core);
    $sensor->queueing($event);
    $sensor->queued($event);

    $record = $buffer->all()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('queued-job')
        ->and($record['name'])->toBe('App\\Jobs\\SendWelcomeEmail')
        ->and($record['connection'])->toBe('redis')
        ->and($record['queue'])->toBe('emails')
        ->and($record['job_id'])->toBe('job-123')
        ->and($record['_group'])->toBe(Group::name('App\\Jobs\\SendWelcomeEmail'))
        ->and($record['trace_id'])->toBe($core->traceId)
        ->and($record['execution_source'])->toBe('request')
        ->and($record['duration'])->toBeInt();
});

it('strips an SQS URL prefix from the queue name', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $event = fakeQueueEvent(
        connectionName: 'sqs',
        queue: 'https://sqs.us-east-1.amazonaws.com/123/emails',
        job: fakeJob('App\\Jobs\\SendWelcomeEmail'),
    );

    $sensor = new QueuedJobSensor($core);
    $sensor->queueing($event);
    $sensor->queued($event);

    expect($buffer->all()[0]['queue'])->toBe('emails');
});

it('uses get_class when the job has no displayName', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $job = new stdClass;
    $event = fakeQueueEvent(job: $job);

    (new QueuedJobSensor($core))->queued($event);

    expect($buffer->all()[0]['name'])->toBe('stdClass');
});

it('uses a string job directly as the name', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $event = fakeQueueEvent(job: 'App\\Jobs\\LegacyJob');

    (new QueuedJobSensor($core))->queued($event);

    expect($buffer->all()[0]['name'])->toBe('App\\Jobs\\LegacyJob');
});

it('records a positive duration when paired', function () {
    $client = new RecordingClient;
    [$core, $buffer, $clock] = makeCore($client, requestRate: 1.0, now: 1000.0);
    $core->prepareForRequest();

    $event = fakeQueueEvent(job: fakeJob('App\\Jobs\\SendWelcomeEmail'));

    $sensor = new QueuedJobSensor($core);
    $sensor->queueing($event);       // captured at t = 1000.0
    $clock->set(1000.002);           // 2 ms later
    $sensor->queued($event);

    expect($buffer->all()[0]['duration'])->toBe(2000);
});

it('emits nothing for a sync connection', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $event = fakeQueueEvent(connectionName: 'sync', job: fakeJob('App\\Jobs\\SendWelcomeEmail'));

    $sensor = new QueuedJobSensor($core);
    $sensor->queueing($event);
    $sensor->queued($event);

    expect($buffer->all())->toBe([])
        ->and($core->counters()['jobs_queued'])->toBe(0);
});

it('increments the jobs_queued counter', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $event = fakeQueueEvent(job: fakeJob('App\\Jobs\\SendWelcomeEmail'));

    (new QueuedJobSensor($core))->queued($event);

    expect($core->counters()['jobs_queued'])->toBe(1);
});

<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\JobAttemptSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;

function fakeAttemptJob(string $name, string $queue = 'default', string $jobId = 'job-uuid', int $attempts = 1): object
{
    return new class($name, $queue, $jobId, $attempts)
    {
        public function __construct(
            private string $name,
            private string $queue,
            private string $jobId,
            private int $attempts,
        ) {}

        public function resolveName(): string
        {
            return $this->name;
        }

        public function getQueue(): string
        {
            return $this->queue;
        }

        public function attempts(): int
        {
            return $this->attempts;
        }

        public function payload(): array
        {
            return ['daywatch' => ['job_id' => $this->jobId]];
        }
    };
}

it('maps a processed job attempt to a job-attempt record', function () {
    $client = new RecordingClient;
    [$core, , $clock] = makeCore($client, now: 2000.0);
    $sensor = new JobAttemptSensor($core);

    $job = fakeAttemptJob('App\\Jobs\\SendReceipt', 'emails', 'jid-1', 1);

    $clock->set(2000.0);
    $sensor->processing((object) ['connectionName' => 'redis', 'job' => $job]);
    $core->sample(); // simulate the trace's sampling decision propagated from dispatch

    $clock->set(2000.152);
    $sensor->processed((object) ['connectionName' => 'redis', 'job' => $job]);

    $record = $client->lastDecoded()[0];

    expect($record['t'])->toBe('job-attempt')
        ->and($record['name'])->toBe('App\\Jobs\\SendReceipt')
        ->and($record['connection'])->toBe('redis')
        ->and($record['queue'])->toBe('emails')
        ->and($record['job_id'])->toBe('jid-1')
        ->and($record['attempt'])->toBe(1)
        ->and($record['status'])->toBe('processed')
        ->and($record['duration'])->toBe(152000)
        ->and($record['attempt_id'])->toBe($core->executionId)
        ->and($record['_group'])->toBe(Group::name('App\\Jobs\\SendReceipt'))
        ->and($record)->toHaveKey('queries');
});

it('records released and failed statuses', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client);
    $sensor = new JobAttemptSensor($core);
    $job = fakeAttemptJob('App\\Jobs\\X');

    $sensor->processing((object) ['connectionName' => 'redis', 'job' => $job]);
    $core->sample();
    $sensor->failed((object) ['connectionName' => 'redis', 'job' => $job]);

    expect($client->lastDecoded()[0]['status'])->toBe('failed');
});

it('skips sync-connection jobs entirely', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client);
    $sensor = new JobAttemptSensor($core);
    $job = fakeAttemptJob('App\\Jobs\\Inline');

    $sensor->processing((object) ['connectionName' => 'sync', 'job' => $job]);
    $core->sample();
    $sensor->processed((object) ['connectionName' => 'sync', 'job' => $job]);

    expect($client->sent)->toBe([])
        ->and($buffer->all())->toBe([]);
});

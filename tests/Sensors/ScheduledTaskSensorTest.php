<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\ScheduledTaskSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;

function fakeTask(string $summary, string $cron = '*/5 * * * *', string $tz = 'UTC'): object
{
    return new class($summary, $cron, $tz)
    {
        public string $expression;

        public string $timezone;

        public bool $withoutOverlapping = true;

        public bool $onOneServer = false;

        public bool $runInBackground = false;

        public function __construct(private string $summary, string $cron, string $tz)
        {
            $this->expression = $cron;
            $this->timezone = $tz;
        }

        public function getSummaryForDisplay(): string
        {
            return $this->summary;
        }
    };
}

it('maps a scheduled task run to a scheduled-task record', function () {
    $client = new RecordingClient;
    [$core, , $clock] = makeCore($client, now: 3000.0);
    $sensor = new ScheduledTaskSensor($core);

    $task = fakeTask('App\\Console\\Commands\\PruneStaleCarts');

    $clock->set(3000.0);
    $sensor->starting((object) ['task' => $task]);

    $clock->set(3000.480);
    $sensor->finished((object) ['task' => $task]);

    $record = $client->lastDecoded()[0];

    expect($record['t'])->toBe('scheduled-task')
        ->and($record['name'])->toBe('App\\Console\\Commands\\PruneStaleCarts')
        ->and($record['cron'])->toBe('*/5 * * * *')
        ->and($record['timezone'])->toBe('UTC')
        ->and($record['without_overlapping'])->toBeTrue()
        ->and($record['on_one_server'])->toBeFalse()
        ->and($record['run_in_background'])->toBeFalse()
        ->and($record['status'])->toBe('processed')
        ->and($record['duration'])->toBe(480000)
        ->and($record['_group'])->toBe(Group::scheduledTask('App\\Console\\Commands\\PruneStaleCarts', '*/5 * * * *', 'UTC'))
        ->and($record)->toHaveKey('queries');
});

it('records skipped and failed statuses', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client);
    $sensor = new ScheduledTaskSensor($core);
    $task = fakeTask('app:task');

    $sensor->starting((object) ['task' => $task]);
    $sensor->skipped((object) ['task' => $task]);

    expect($client->lastDecoded()[0]['status'])->toBe('skipped');
});

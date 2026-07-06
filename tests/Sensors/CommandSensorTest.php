<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\CommandSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;

function fakeCommandStarting(string $name, string $input = ''): object
{
    return new class($name, $input)
    {
        public string $command;

        public object $input;

        public function __construct(string $name, string $input)
        {
            $this->command = $name;
            $this->input = new class($input !== '' ? $input : $name)
            {
                public function __construct(private string $s) {}

                public function __toString(): string
                {
                    return $this->s;
                }
            };
        }
    };
}

it('maps an artisan command lifecycle to a command record', function () {
    $client = new RecordingClient;
    [$core, , $clock] = makeCore($client, now: 1000.0);
    $sensor = new CommandSensor($core);

    $clock->set(1000.010);
    $sensor->starting(fakeCommandStarting('inventory:sync', 'inventory:sync --chunk=500'));

    $clock->set(1002.150);
    $sensor->finished((object) ['command' => 'inventory:sync', 'exitCode' => 0]);

    $record = $client->lastDecoded()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('command')
        ->and($record['name'])->toBe('inventory:sync')
        ->and($record['command'])->toBe('inventory:sync --chunk=500')
        ->and($record['exit_code'])->toBe(0)
        ->and($record['_group'])->toBe(Group::name('inventory:sync'))
        ->and($record['duration'])->toBe($record['bootstrap'] + $record['action'] + $record['terminating'])
        ->and($record)->toHaveKey('queries')
        ->and($record)->toHaveKey('peak_memory_usage');
});

it('captures a non-zero exit code', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client);
    $sensor = new CommandSensor($core);

    $sensor->starting(fakeCommandStarting('app:broken'));
    $sensor->finished((object) ['command' => 'app:broken', 'exitCode' => 1]);

    expect($client->lastDecoded()[0]['exit_code'])->toBe(1);
});

it('never records the daemon or other ignored commands', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client);
    $sensor = new CommandSensor($core);

    $sensor->starting(fakeCommandStarting('daywatch:agent'));
    $sensor->finished((object) ['command' => 'daywatch:agent', 'exitCode' => 0]);

    expect($client->sent)->toBe([])
        ->and($buffer->all())->toBe([]);
});

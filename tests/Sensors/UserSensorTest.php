<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\UserSensor;
use Daywatch\Agent\Tests\Support\RecordingClient;
use Illuminate\Auth\GenericUser;

it('emits a user record for the authenticated user', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client);
    $core->prepareForRequest();

    $this->actingAs(new GenericUser(['id' => 42, 'name' => 'Ada Lovelace', 'username' => 'ada']));

    (new UserSensor($core))->capture();

    $record = $buffer->all()[0];

    expect($record['t'])->toBe('user')
        ->and($record['id'])->toBe('42')
        ->and($record['name'])->toBe('Ada Lovelace')
        ->and($record['username'])->toBe('ada')
        ->and($record)->not->toHaveKey('_group')
        ->and($record)->not->toHaveKey('trace_id');
});

it('emits nothing when there is no authenticated user', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client);
    $core->prepareForRequest();

    (new UserSensor($core))->capture();

    expect($buffer->all())->toBe([]);
});

it('honours a user customiser', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client);
    $core->prepareForRequest();

    $this->actingAs(new GenericUser(['id' => 7, 'name' => 'x', 'username' => 'y']));

    $sensor = new UserSensor($core);
    $sensor->using(fn ($user): array => ['id' => '99', 'name' => 'Custom', 'username' => 'custom']);
    $sensor->capture();

    $record = $buffer->all()[0];

    expect($record['id'])->toBe('99')
        ->and($record['name'])->toBe('Custom')
        ->and($record['username'])->toBe('custom');
});

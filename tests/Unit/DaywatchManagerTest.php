<?php

declare(strict_types=1);

use Daywatch\Agent\Buffer\RecordsBuffer;
use Daywatch\Agent\Core;
use Daywatch\Agent\Daywatch;
use Daywatch\Agent\Sensors\ExceptionSensor;
use Daywatch\Agent\Tests\Support\FrozenClock;
use Daywatch\Agent\Tests\Support\RecordingClient;

/**
 * The public API behind the Daywatch facade is the LAST guard of the cardinal
 * rule: even when a collaborator lets an exception escape (a Core regression,
 * a TypeError), nothing may reach the host app and the fluent chain must
 * survive. Core/ExceptionSensor guard their own internals — this pins the
 * manager as an independent second line of defence.
 */
function explodingCore(): Core
{
    return new class(new RecordsBuffer(1), new RecordingClient, new FrozenClock(1000.0)) extends Core
    {
        public function user(string|int|callable|null $id): void
        {
            throw new RuntimeException('core exploded');
        }

        public function sample(): void
        {
            throw new RuntimeException('core exploded');
        }

        public function dontSample(): void
        {
            throw new RuntimeException('core exploded');
        }

        public function pause(): void
        {
            throw new RuntimeException('core exploded');
        }

        public function resume(): void
        {
            throw new RuntimeException('core exploded');
        }

        public function digest(): void
        {
            throw new RuntimeException('core exploded');
        }

        public function markIgnored(Throwable $e): void
        {
            throw new RuntimeException('core exploded');
        }

        public function isIgnored(Throwable $e): bool
        {
            throw new RuntimeException('core exploded');
        }

        public function recordException(array $record, string $preview): void
        {
            throw new RuntimeException('core exploded');
        }
    };
}

it('never throws and stays fluent when the core explodes', function () {
    $manager = new Daywatch(explodingCore(), new ExceptionSensor(explodingCore()));

    expect($manager->user('1'))->toBe($manager)
        ->and($manager->sample())->toBe($manager)
        ->and($manager->dontSample())->toBe($manager)
        ->and($manager->report(new RuntimeException('boom')))->toBe($manager)
        ->and($manager->ignore(new RuntimeException('ignored')))->toBe($manager)
        ->and($manager->pause())->toBe($manager)
        ->and($manager->resume())->toBe($manager)
        ->and($manager->digest())->toBe($manager);
});

it('resolves a disabled no-op Daywatch when the core cannot be built', function () {
    // The provider's facade-root factory is guarded: a Core that fails to
    // build (broken config, missing binding) must not make the facade
    // unresolvable — a disabled no-op manager is returned instead.
    app()->bind(Core::class, fn () => throw new RuntimeException('unbuildable'));

    $manager = app(Daywatch::class);

    expect($manager)->toBeInstanceOf(Daywatch::class)
        ->and($manager->sample()->user('1')->digest())->toBe($manager);
});

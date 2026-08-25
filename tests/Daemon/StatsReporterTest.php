<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\DaemonStats;
use Daywatch\Agent\Daemon\StatsReporter;
use Daywatch\Agent\Tests\Support\FakeScheduler;
use Daywatch\Agent\Tests\Support\FrozenClock;

it('arms a timer at the configured interval and emits one line per fire', function () {
    $scheduler = new FakeScheduler;
    $lines = [];

    $reporter = new StatsReporter(
        new DaemonStats('https://daywatch.example.com', new FrozenClock(1000.0)),
        $scheduler,
        function (string $line) use (&$lines): void {
            $lines[] = $line;
        },
        60,
    );

    $reporter->start();

    expect($scheduler->delays())->toBe([60.0])
        ->and($lines)->toBe([]);

    $scheduler->fireNext(); // virtual clock: 60 s elapse

    expect($lines)->toHaveCount(1)
        ->and($lines[0])->toStartWith('[daywatch:agent] stats target=https://daywatch.example.com')
        ->and($scheduler->delays())->toBe([60.0]); // re-armed for the next interval

    $scheduler->fireNext();

    expect($lines)->toHaveCount(2);
});

it('is disabled entirely when the interval is zero', function () {
    $scheduler = new FakeScheduler;

    $reporter = new StatsReporter(
        new DaemonStats('https://daywatch.example.com', new FrozenClock(1.0)),
        $scheduler,
        fn (string $line) => null,
        0,
    );

    $reporter->start();

    expect($scheduler->pending())->toBe(0);
});

it('survives a logger that throws — and keeps the timer armed', function () {
    $scheduler = new FakeScheduler;

    $reporter = new StatsReporter(
        new DaemonStats('https://daywatch.example.com', new FrozenClock(1.0)),
        $scheduler,
        fn (string $line) => throw new RuntimeException('stdout gone'),
        60,
    );

    $reporter->start();
    $scheduler->fireNext(); // must not throw

    expect($scheduler->pending())->toBe(1); // rescheduled despite the failure
});

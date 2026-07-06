<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\ConsoleDashboard;
use Daywatch\Agent\Daemon\DaemonStats;
use Daywatch\Agent\Daemon\RecentLog;
use Daywatch\Agent\Support\FrozenClock;
use Daywatch\Agent\Tests\Support\FakeScheduler;

/**
 * @param  array{0:int,1:int}  $memory
 * @return array{0: ConsoleDashboard, 1: ArrayObject<int,string>, 2: FakeScheduler, 3: DaemonStats, 4: RecentLog, 5: FrozenClock}
 */
function makeDashboard(int $refresh = 3, array $memory = [24 * 1024 * 1024, 30 * 1024 * 1024]): array
{
    $clock = new FrozenClock(1000.0);
    $stats = new DaemonStats('https://daywatch.example.com', $clock);
    $recent = new RecentLog(10);
    $scheduler = new FakeScheduler;
    $frames = new ArrayObject; // shared reference survives list() destructuring

    $dashboard = new ConsoleDashboard(
        $stats,
        $recent,
        $clock,
        $scheduler,
        static fn (string $s) => $frames->append($s),
        '127.0.0.1:2408',
        $refresh,
        static fn (): array => $memory,
    );

    return [$dashboard, $frames, $scheduler, $stats, $recent, $clock];
}

it('paints an immediate frame and arms the repaint timer on start', function () {
    [$dashboard, $frames, $scheduler] = makeDashboard(3);

    $dashboard->start();

    expect($frames)->toHaveCount(1)
        ->and($scheduler->delays())->toBe([3.0]);
});

it('renders memory, uptime, target, throughput and auth errors', function () {
    [$dashboard, $frames, , $stats] = makeDashboard();
    $stats->authFailed();
    $stats->authFailed();

    $dashboard->render();

    $frame = $frames[0];

    expect($frame)->toContain('Daywatch agent')
        ->toContain('up 0:00:00')
        ->toContain('listening 127.0.0.1:2408')
        ->toContain('target https://daywatch.example.com')
        ->toContain('memory     24.0 MB')
        ->toContain('peak 30.0 MB')
        ->toContain('auth errors 2')
        ->toContain('last flush never');
});

it('shows the last N log lines from the ring buffer', function () {
    [$dashboard, $frames, , , $recent] = makeDashboard();
    $recent->push('[daywatch:agent] 401 unauthorized — bad token, dropping batch');

    $dashboard->render();

    expect($frames[0])->toContain('recent')
        ->toContain('401 unauthorized — bad token');
});

it('says nothing yet when no lines have been logged', function () {
    [$dashboard, $frames] = makeDashboard();

    $dashboard->render();

    expect($frames[0])->toContain('(nothing yet)');
});

it('redraws in place — later frames move the cursor up over the previous frame', function () {
    [$dashboard, $frames, $scheduler] = makeDashboard(3);

    $dashboard->start();       // first paint: clear-down, no up-move
    $scheduler->fireNext();    // second paint: must move the cursor up first

    expect($frames)->toHaveCount(2)
        ->and($frames[0])->toStartWith("\e[0J")
        ->and($frames[1])->toMatch("/^\e\\[\\d+A\e\\[0J/")
        ->and($scheduler->delays())->toBe([3.0]); // re-armed
});

it('is disabled entirely when the refresh interval is zero', function () {
    [$dashboard, $frames, $scheduler] = makeDashboard(0);

    $dashboard->start();

    expect($frames)->toHaveCount(0)
        ->and($scheduler->pending())->toBe(0);
});

it('survives a writer that throws and keeps the timer armed', function () {
    $clock = new FrozenClock(1000.0);
    $scheduler = new FakeScheduler;

    $dashboard = new ConsoleDashboard(
        new DaemonStats('https://daywatch.example.com', $clock),
        new RecentLog(10),
        $clock,
        $scheduler,
        fn (string $s) => throw new RuntimeException('stdout gone'),
        '127.0.0.1:2408',
        3,
        static fn (): array => [0, 0],
    );

    $dashboard->start();       // must not throw
    $scheduler->fireNext();    // must not throw

    expect($scheduler->pending())->toBe(1); // rescheduled despite the failure
});

it('reports the flush age once records have flushed', function () {
    [$dashboard, $frames, , $stats, , $clock] = makeDashboard();
    $stats->received(5, 100);
    $stats->flushed();
    $clock->advance(4.0);

    $dashboard->render();

    expect($frames[0])->toContain('last flush 4s ago')
        ->toContain('(5 records)');
});

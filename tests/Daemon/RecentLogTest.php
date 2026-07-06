<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\RecentLog;

it('keeps only the last N lines, oldest evicted first', function () {
    $log = new RecentLog(3);

    $log->push('a');
    $log->push('b');
    $log->push('c');
    $log->push('d');

    expect($log->lines())->toBe(['b', 'c', 'd']);
});

it('tracks the total pushed even after eviction', function () {
    $log = new RecentLog(2);

    $log->push('a');
    $log->push('b');
    $log->push('c');

    expect($log->total())->toBe(3)
        ->and($log->lines())->toBe(['b', 'c']);
});

it('starts empty', function () {
    expect((new RecentLog)->lines())->toBe([])
        ->and((new RecentLog)->total())->toBe(0);
});

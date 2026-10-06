<?php

declare(strict_types=1);

use Daywatch\Agent\Support\Patterns;

it('matches plain substring patterns as needles', function () {
    $patterns = new Patterns(['*illuminate:*', '*jobs*']);

    expect($patterns->matches('laravel_cache_illuminate:queue:restart'))->toBeTrue()
        ->and($patterns->matches('select * from "jobs" where "queue" = ?'))->toBeTrue()
        ->and($patterns->matches('select * from `jobs` where `queue` = ?'))->toBeTrue()
        ->and($patterns->matches('select * from "users"'))->toBeFalse();
});

it('matches real glob structure with Str::is()', function () {
    $patterns = new Patterns(['insert into jobs*', 'App\Jobs\Send*']);

    expect($patterns->matches('insert into jobs (queue) values (?)'))->toBeTrue()
        ->and($patterns->matches('App\Jobs\SendWelcomeEmail'))->toBeTrue()
        ->and($patterns->matches('select * from jobs'))->toBeFalse()
        ->and($patterns->matches('App\Jobs\Other'))->toBeFalse();
});

it('matches nothing when no patterns are configured', function () {
    expect((new Patterns([]))->matches('illuminate:queue:restart'))->toBeFalse();
});

it('normalises arrays and comma-separated strings with from()', function () {
    expect(Patterns::from(' *jobs* , *cache* ')->matches('select * from `cache`'))->toBeTrue()
        ->and(Patterns::from(['*jobs*', ' *sessions* ', ''])->matches('delete from "sessions"'))->toBeTrue()
        ->and(Patterns::from('')->matches('anything'))->toBeFalse()
        ->and(Patterns::from(null)->matches('anything'))->toBeFalse();
});

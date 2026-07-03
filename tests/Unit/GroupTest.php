<?php

declare(strict_types=1);

use Daywatch\Agent\Support\Group;

it('hashes the request group as xxh128(methods|,domain,path)', function () {
    expect(Group::request(['GET', 'HEAD'], '', '/orders/{order}'))
        ->toBe(hash('xxh128', 'GET|HEAD,,/orders/{order}'));
});

it('hashes the exception group as xxh128(class|code|file|line)', function () {
    expect(Group::exception('App\\Boom', '0', 'app/Foo.php', 42))
        ->toBe(hash('xxh128', 'App\\Boom|0|app/Foo.php|42'));
});

it('hashes the query group over connection + normalized SQL', function () {
    expect(Group::query('mysql', 'select * from `orders` where `id` = ?'))
        ->toBe(hash('xxh128', 'mysql,select * from `orders` where `id` = ?'));
});

it('produces a 32-char xxh128 hex digest', function () {
    expect(Group::request(['GET'], '', '/'))->toHaveLength(32);
});

it('collapses IN (?, ?, ...) lists so parameter count does not fragment groups', function () {
    $a = Group::query('mysql', 'select * from users where id in (?, ?, ?)');
    $b = Group::query('mysql', 'select * from users where id in (?, ?, ?, ?, ?)');

    expect($a)->toBe($b)
        ->and($a)->toBe(hash('xxh128', 'mysql,select * from users where id in (?)'));
});

it('collapses bulk VALUES tuples', function () {
    $a = Group::query('pgsql', 'insert into t (a) values (?), (?), (?)');
    $b = Group::query('pgsql', 'insert into t (a) values (?)');

    expect($a)->toBe($b);
});

it('normalizes surrounding whitespace before grouping', function () {
    expect(Group::normalizeSql("  select   1  \n from dual "))->toBe('select 1 from dual');
});

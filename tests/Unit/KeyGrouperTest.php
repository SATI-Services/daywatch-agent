<?php

declare(strict_types=1);

use Daywatch\Agent\Support\KeyGrouper;

it('passes values through unchanged when no groups are configured', function () {
    expect((new KeyGrouper)->group('sys_setting_abc123'))->toBe('sys_setting_abc123');
});

it('passes unmatched values through unchanged', function () {
    $grouper = new KeyGrouper(['#^sys_setting_.*$#' => 'sys_setting:*']);

    expect($grouper->group('users:1'))->toBe('users:1');
});

it('rewrites a matched value to its label', function () {
    $grouper = new KeyGrouper(['#^sys_setting_.*$#' => 'sys_setting:*']);

    expect($grouper->group('sys_setting_5753f25f3ab0b7e9442c9a528ab9efc1'))->toBe('sys_setting:*');
});

it('stops at the first matching pattern', function () {
    $grouper = new KeyGrouper([
        '#^sys_setting_admin.*$#' => 'sys_setting:admin',
        '#^sys_setting_.*$#' => 'sys_setting:*',
    ]);

    expect($grouper->group('sys_setting_admin_123'))->toBe('sys_setting:admin')
        ->and($grouper->group('sys_setting_other'))->toBe('sys_setting:*');
});

it('supports capture groups in the replacement', function () {
    $grouper = new KeyGrouper(['#^user:(\d+):.*$#' => 'user:$1:*']);

    expect($grouper->group('user:42:profile'))->toBe('user:42:*');
});

it('drops invalid patterns at construction instead of throwing or warning', function () {
    $grouper = new KeyGrouper([
        '#unterminated' => 'broken:*', // compilation failure
        '/valid/iZ' => 'broken:*', // unknown modifier
        '#^sys_setting_.*$#' => 'sys_setting:*',
    ]);

    expect($grouper->group('sys_setting_abc123'))->toBe('sys_setting:*')
        ->and($grouper->group('anything'))->toBe('anything');
});

it('builds from a config value, ignoring non-string or empty entries', function () {
    expect(KeyGrouper::from(null)->group('k'))->toBe('k')
        ->and(KeyGrouper::from('not,a,map')->group('k'))->toBe('k')
        ->and(KeyGrouper::from(['#^a.*$#' => 'a:*', 0 => 'orphan', '' => 'blank'])->group('abc'))->toBe('a:*');
});

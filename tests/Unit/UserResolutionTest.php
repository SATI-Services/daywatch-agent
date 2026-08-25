<?php

declare(strict_types=1);

use Daywatch\Agent\Tests\Support\RecordingClient;

/**
 * Integrity of `Daywatch::user()` / Core::resolveUser(). The failure modes guarded
 * here are the dangerous kind: invoking host code that was only ever meant to be a
 * user id, and unbounded recursion through a resolver that itself emits telemetry.
 */
it('treats a string id that happens to name a global function as an id, not a resolver', function () {
    $called = 0;

    // A real global function with the same name as the id we are about to set.
    if (! function_exists('daywatch_probe_helper')) {
        eval('function daywatch_probe_helper($user) { $GLOBALS["daywatch_probe_calls"]++; return "hijacked"; }');
    }

    $GLOBALS['daywatch_probe_calls'] = 0;

    [$core] = makeCore(new RecordingClient);

    expect(is_callable('daywatch_probe_helper'))->toBeTrue(); // the trap this guards

    $core->user('daywatch_probe_helper');

    expect($core->resolveUser())->toBe('daywatch_probe_helper')
        ->and($GLOBALS['daywatch_probe_calls'])->toBe(0)
        ->and($called)->toBe(0);
});

it('treats an integer id as an id', function () {
    [$core] = makeCore(new RecordingClient);

    $core->user(42);

    expect($core->resolveUser())->toBe('42');
});

it('still honours a closure resolver', function () {
    [$core] = makeCore(new RecordingClient);

    $core->user(fn ($user) => 'resolved-7');

    expect($core->resolveUser())->toBe('resolved-7');
});

it('honours an invokable object as a resolver', function () {
    [$core] = makeCore(new RecordingClient);

    $core->user(new class
    {
        public function __invoke($user): string
        {
            return 'invokable-9';
        }
    });

    expect($core->resolveUser())->toBe('invokable-9');
});

it('memoises a non-empty resolution so a resolver runs once per execution', function () {
    $calls = 0;

    [$core] = makeCore(new RecordingClient);

    $core->user(function ($user) use (&$calls) {
        $calls++;

        return 'u1';
    });

    expect($core->resolveUser())->toBe('u1')
        ->and($core->resolveUser())->toBe('u1')
        ->and($core->resolveUser())->toBe('u1')
        ->and($calls)->toBe(1);
});

it('does not memoise an empty resolution, so a later sign-in is still picked up', function () {
    $id = '';

    [$core] = makeCore(new RecordingClient);

    $core->user(function ($user) use (&$id) {
        return $id;
    });

    expect($core->resolveUser())->toBe('');

    $id = 'now-authenticated';

    expect($core->resolveUser())->toBe('now-authenticated');
});

it('cannot recurse: a resolver that resolves the user again terminates', function () {
    [$core] = makeCore(new RecordingClient);

    $depth = 0;

    $core->user(function ($user) use (&$core, &$depth) {
        $depth++;

        // A resolver that touches the DB or logs re-enters a sensor, which
        // resolves the user again. The latch must break the cycle.
        return $core->resolveUser() === '' ? 'outer' : 'inner';
    });

    expect($core->resolveUser())->toBe('outer')
        ->and($depth)->toBe(1);
});

it('re-resolves after reset (a fresh execution gets a fresh user)', function () {
    $ids = ['first', 'second'];

    [$core] = makeCore(new RecordingClient);

    $core->user(function ($user) use (&$ids) {
        return array_shift($ids);
    });

    expect($core->resolveUser())->toBe('first');

    $core->reset();

    // reset() clears the resolver as well as the memo — an explicit id proves
    // the memo itself did not survive.
    $core->user('second');

    expect($core->resolveUser())->toBe('second');
});

it('clears the memo when a new id is set mid-execution', function () {
    [$core] = makeCore(new RecordingClient);

    $core->user('before');
    expect($core->resolveUser())->toBe('before');

    $core->user('after');
    expect($core->resolveUser())->toBe('after');
});

it('swallows a resolver that throws', function () {
    [$core] = makeCore(new RecordingClient);

    $core->user(function ($user) {
        throw new RuntimeException('resolver exploded');
    });

    expect($core->resolveUser())->toBe('');
});

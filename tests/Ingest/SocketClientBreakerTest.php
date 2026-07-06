<?php

declare(strict_types=1);

use Daywatch\Agent\Ingest\SocketClient;

// 127.0.0.1:1 has nothing listening → connect is refused instantly (the assumed
// "daemon not running" case). We assert the breaker's observable effect via the
// public connectionAttempts counter, with no reliance on wall-clock timing.

it('suppresses repeat socket attempts while the breaker is open', function () {
    $client = new SocketClient('127.0.0.1:1', 'deadbee', 0.2, 0.2, failureCooldown: 10.0);

    expect($client->send('[]'))->toBeFalse();   // attempt 1 → fails → trips breaker
    expect($client->send('[]'))->toBeFalse();   // breaker open → short-circuits
    expect($client->send('[]'))->toBeFalse();

    expect($client->connectionAttempts)->toBe(1);
});

it('attempts every time when the breaker is disabled (cooldown 0)', function () {
    $client = new SocketClient('127.0.0.1:1', 'deadbee', 0.2, 0.2, failureCooldown: 0.0);

    $client->send('[]');
    $client->send('[]');

    expect($client->connectionAttempts)->toBe(2);
});

it('reopens attempts once the cooldown elapses', function () {
    // Override the clock seam to advance past the cooldown deterministically.
    $client = new class('127.0.0.1:1', 'deadbee', 0.2, 0.2, 5.0) extends SocketClient
    {
        public float $fakeNow = 1000.0;

        protected function now(): float
        {
            return $this->fakeNow;
        }
    };

    $client->send('[]');              // attempt 1 → trips until 1005.0
    $client->send('[]');              // still 1000.0 → suppressed
    expect($client->connectionAttempts)->toBe(1);

    $client->fakeNow = 1006.0;        // past the cooldown window
    $client->send('[]');              // attempt 2
    expect($client->connectionAttempts)->toBe(2);
});

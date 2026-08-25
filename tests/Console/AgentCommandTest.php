<?php

declare(strict_types=1);

it('fails fast (without booting the loop) when base_url is not configured', function () {
    config(['daywatch.base_url' => null]);

    $this->artisan('daywatch:agent')
        ->expectsOutputToContain('DAYWATCH_BASE_URL is not set')
        ->assertExitCode(1);
});

it('explains an already-taken listen address instead of leaking a socket error', function () {
    config(['daywatch.base_url' => 'https://daywatch.test']);

    // Hold a real port so the daemon's TcpServer cannot bind it.
    $holder = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    expect($holder)->not->toBeFalse();

    $address = stream_socket_get_name($holder, false); // 127.0.0.1:<port>
    $port = substr((string) strrchr($address, ':'), 1);

    try {
        $this->artisan('daywatch:agent', ['--listen' => $address, '--no-auth-check' => true])
            ->expectsOutputToContain('cannot listen on '.$address)
            ->expectsOutputToContain('Another daywatch:agent daemon is most likely already running.')
            ->expectsOutputToContain('php artisan daywatch:status')
            ->expectsOutputToContain('lsof -nP -iTCP:'.$port)
            ->assertExitCode(1);
    } finally {
        fclose($holder);
    }
});

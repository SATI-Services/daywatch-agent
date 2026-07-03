<?php

declare(strict_types=1);

use Daywatch\Agent\Ingest\Payload;
use Daywatch\Agent\Ingest\SocketClient;
use Daywatch\Agent\Tests\Support\StubTcpServer;

it('sends an exact v1 frame and returns true on the 2:OK ack', function () {
    $server = new StubTcpServer('2:OK');

    try {
        $client = new SocketClient($server->uri(), 'abc1234');

        $ok = $client->send('[{"t":"query"}]');

        expect($ok)->toBeTrue()
            ->and($server->capturedFrame())->toBe(Payload::frame('[{"t":"query"}]', 'abc1234'));
    } finally {
        $server->stop();
    }
});

it('sends a PING frame for reachability probes', function () {
    $server = new StubTcpServer('2:OK');

    try {
        $client = new SocketClient($server->uri(), 'abc1234');

        expect($client->ping())->toBeTrue()
            ->and($server->capturedFrame())->toBe(Payload::frame('PING', 'abc1234'));
    } finally {
        $server->stop();
    }
});

it('round-trips a STATS frame and decodes the counters reply', function () {
    $counters = [
        'base_url' => 'https://daywatch.example.com',
        'records_received' => 3,
        'records_buffered' => 1,
        'last_flush_at' => null,
    ];
    $json = json_encode($counters);
    $server = new StubTcpServer('2:OK', strlen($json).':'.$json);

    try {
        $client = new SocketClient($server->uri(), 'abc1234');

        expect($client->stats())->toBe($counters)
            ->and($server->capturedFrame())->toBe(Payload::frame('STATS', 'abc1234'));
    } finally {
        $server->stop();
    }
});

it('returns null stats when the daemon acks but sends no reply', function () {
    // A pre-STATS daemon (or token mismatch) acks and closes without a reply.
    $server = new StubTcpServer('2:OK');

    try {
        expect((new SocketClient($server->uri(), 'abc1234'))->stats())->toBeNull();
    } finally {
        $server->stop();
    }
});

it('returns null stats on a malformed reply', function (string $reply) {
    $server = new StubTcpServer('2:OK', $reply);

    try {
        expect((new SocketClient($server->uri(), 'abc1234'))->stats())->toBeNull();
    } finally {
        $server->stop();
    }
})->with([
    'not length-prefixed' => 'garbage',
    'length lies (truncated body)' => '999:{"a"',
    'zero length' => '0:',
    'not json' => '9:not-json!',
    'json but not an object' => '4:true',
]);

it('returns false when the ack is not 2:OK', function () {
    $server = new StubTcpServer('bad');

    try {
        expect((new SocketClient($server->uri(), 'abc1234'))->send('[{"a":1}]'))->toBeFalse();
    } finally {
        $server->stop();
    }
});

it('returns false when the daemon closes without acking', function () {
    $server = new StubTcpServer('none');

    try {
        expect((new SocketClient($server->uri(), 'abc1234'))->send('[{"a":1}]'))->toBeFalse();
    } finally {
        $server->stop();
    }
});

it('returns false (never throws) when the socket dies mid-exchange', function () {
    $server = new StubTcpServer('close');

    try {
        expect((new SocketClient($server->uri(), 'abc1234'))->send('[{"a":1}]'))->toBeFalse();
    } finally {
        $server->stop();
    }
});

it('returns false (never throws) when the daemon is dead', function () {
    // A just-freed port → connection refused, fast and deterministic.
    $probe = stream_socket_server('tcp://127.0.0.1:0');
    $addr = stream_socket_get_name($probe, false);
    $port = substr($addr, strrpos($addr, ':') + 1);
    fclose($probe);

    $client = new SocketClient('127.0.0.1:'.$port, 'abc1234');

    expect($client->send('[{"a":1}]'))->toBeFalse()
        ->and($client->ping())->toBeFalse();
});

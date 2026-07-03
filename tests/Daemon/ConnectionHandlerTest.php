<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\ConnectionHandler;
use Daywatch\Agent\Ingest\Payload;
use Daywatch\Agent\Tests\Support\SpyRecordSink;

function makeConnectionHandler(SpyRecordSink $sink, array &$acks, array &$flags, string $expected = 'abc1234'): ConnectionHandler
{
    $flags = ['unknown' => false, 'error' => null];

    return new ConnectionHandler(
        expectedTokenHash: $expected,
        server: $sink,
        ackWriter: function (string $ack) use (&$acks): void {
            $acks[] = $ack;
        },
        onUnknownVersion: function () use (&$flags): void {
            $flags['unknown'] = true;
        },
        onError: function (string $message) use (&$flags): void {
            $flags['error'] = $message;
        },
    );
}

it('acks and forwards a valid v1 frame with a matching token', function () {
    $sink = new SpyRecordSink;
    $acks = [];
    $flags = [];
    $handler = makeConnectionHandler($sink, $acks, $flags);

    $handler->feed(Payload::frame('[{"t":"query"}]', 'abc1234'));

    expect($acks)->toBe(['2:OK'])
        ->and($sink->ingested)->toBe(['[{"t":"query"}]']);
});

it('acks but drops a frame with a mismatched token', function () {
    $sink = new SpyRecordSink;
    $acks = [];
    $flags = [];
    $handler = makeConnectionHandler($sink, $acks, $flags, expected: 'abc1234');

    $handler->feed(Payload::frame('[{"t":"query"}]', 'wrong99'));

    expect($acks)->toBe(['2:OK'])       // ack is framing-level, always sent
        ->and($sink->ingested)->toBe([]); // but the payload is not buffered
});

it('acks a PING without forwarding it', function () {
    $sink = new SpyRecordSink;
    $acks = [];
    $flags = [];
    $handler = makeConnectionHandler($sink, $acks, $flags);

    $handler->feed(Payload::frame('PING', 'abc1234'));

    expect($acks)->toBe(['2:OK'])
        ->and($sink->ingested)->toBe([]);
});

it('triggers the graceful-exit path on an unknown frame version', function () {
    $sink = new SpyRecordSink;
    $acks = [];
    $flags = [];
    $handler = makeConnectionHandler($sink, $acks, $flags);

    $handler->feed(Payload::frame('[{"t":"query"}]', 'abc1234', 'v2'));

    expect($flags['unknown'])->toBeTrue()
        ->and($sink->ingested)->toBe([]);
});

it('closes the connection on malformed framing', function () {
    $sink = new SpyRecordSink;
    $acks = [];
    $flags = [];
    $handler = makeConnectionHandler($sink, $acks, $flags);

    $handler->feed('total-garbage-no-length-prefix-here-and-more');

    expect($flags['error'])->not->toBeNull()
        ->and($sink->ingested)->toBe([]);
});

it('replies to a STATS frame with the ack followed by a length-prefixed JSON mini-frame', function () {
    $sink = new SpyRecordSink;
    $writes = [];
    $json = '{"records_received":3}';

    $handler = new ConnectionHandler(
        expectedTokenHash: 'abc1234',
        server: $sink,
        ackWriter: function (string $bytes) use (&$writes): void {
            $writes[] = $bytes;
        },
        statsResponder: static fn (): string => $json,
    );

    $handler->feed(Payload::frame('STATS', 'abc1234'));

    expect($writes)->toBe(['2:OK', strlen($json).':'.$json])
        ->and($sink->ingested)->toBe([]);
});

it('acks a STATS frame but sends no reply on a token mismatch', function () {
    $sink = new SpyRecordSink;
    $writes = [];

    $handler = new ConnectionHandler(
        expectedTokenHash: 'abc1234',
        server: $sink,
        ackWriter: function (string $bytes) use (&$writes): void {
            $writes[] = $bytes;
        },
        statsResponder: static fn (): string => '{"never":"sent"}',
    );

    $handler->feed(Payload::frame('STATS', 'wrong99'));

    expect($writes)->toBe(['2:OK']);
});

it('acks a STATS frame but sends no reply when no responder is wired', function () {
    $sink = new SpyRecordSink;
    $writes = [];

    $handler = new ConnectionHandler(
        expectedTokenHash: 'abc1234',
        server: $sink,
        ackWriter: function (string $bytes) use (&$writes): void {
            $writes[] = $bytes;
        },
    );

    $handler->feed(Payload::frame('STATS', 'abc1234'));

    expect($writes)->toBe(['2:OK']);
});

it('swallows a stats responder that throws — ack only, no exception', function () {
    $sink = new SpyRecordSink;
    $writes = [];

    $handler = new ConnectionHandler(
        expectedTokenHash: 'abc1234',
        server: $sink,
        ackWriter: function (string $bytes) use (&$writes): void {
            $writes[] = $bytes;
        },
        statsResponder: static fn (): string => throw new RuntimeException('counters exploded'),
    );

    $handler->feed(Payload::frame('STATS', 'abc1234'));

    expect($writes)->toBe(['2:OK']);
});

it('processes multiple pipelined frames from one chunk', function () {
    $sink = new SpyRecordSink;
    $acks = [];
    $flags = [];
    $handler = makeConnectionHandler($sink, $acks, $flags);

    $handler->feed(
        Payload::frame('[{"a":1}]', 'abc1234').Payload::frame('[{"b":2}]', 'abc1234'),
    );

    expect($acks)->toBe(['2:OK', '2:OK'])
        ->and($sink->ingested)->toBe(['[{"a":1}]', '[{"b":2}]']);
});

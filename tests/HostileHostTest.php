<?php

declare(strict_types=1);

use Daywatch\Agent\Core;
use Daywatch\Agent\Daemon\ConnectionHandler;
use Daywatch\Agent\Daemon\DaemonStats;
use Daywatch\Agent\Daemon\FrameParser;
use Daywatch\Agent\Daemon\StatsReporter;
use Daywatch\Agent\Daywatch as DaywatchRuntime;
use Daywatch\Agent\Ingest\Client;
use Daywatch\Agent\Ingest\Payload;
use Daywatch\Agent\Ingest\SocketClient;
use Daywatch\Agent\Sensors\ExceptionSensor;
use Daywatch\Agent\Support\FrozenClock;
use Daywatch\Agent\Tests\Support\FakeScheduler;
use Daywatch\Agent\Tests\Support\RecordingClient;
use Daywatch\Agent\Tests\Support\SpyRecordSink;

/*
 * The cardinal rule: nothing this package does may throw into the host app.
 * Every failure mode below must be swallowed — telemetry loss is fine, a host
 * crash never is.
 */

it('a full digest to a dead daemon never throws', function () {
    // Point the real SocketClient at a just-freed (refused) port.
    $probe = stream_socket_server('tcp://127.0.0.1:0');
    $addr = stream_socket_get_name($probe, false);
    $port = substr($addr, strrpos($addr, ':') + 1);
    fclose($probe);

    config(['daywatch.ingest.uri' => '127.0.0.1:'.$port]);

    $core = $this->app->make(Core::class);
    $core->prepareForRequest();
    $core->write(['t' => 'x']);
    $core->digest();

    expect(true)->toBeTrue();
});

it('the facade swallows a transport that throws mid-send', function () {
    $client = new RecordingClient;
    $client->throwOnSend = true;
    $this->app->instance(Client::class, $client);

    $core = $this->app->make(Core::class); // resolves with the throwing client
    $core->prepareForRequest();
    $core->write(['t' => 'x']);

    $this->app->make(DaywatchRuntime::class)->digest(); // must not throw

    expect(true)->toBeTrue();
});

it('truncates an oversized field to its byte cap instead of ballooning', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new ExceptionSensor($core))->report(new RuntimeException(str_repeat('x', 100_000)));

    expect(strlen($buffer->all()[0]['message']))->toBe(65_535);
});

it('never throws on arbitrary garbage fed to the frame parser', function (string $garbage) {
    $parser = new FrameParser;

    $parser->push($garbage); // must not throw; either parses, buffers, or errors

    expect(true)->toBeTrue();
})->with([
    'pure junk' => 'not a frame at all',
    'huge declared length' => '999999999:v1:abc1234:[',
    'negative-looking' => '-5:v1:abc1234:[]',
    'empty' => '',
    'only colon' => ':',
    'binary' => "\x00\x01\x02\xff:garbage",
]);

it('drops a wrong-token frame at the daemon without throwing or forwarding', function () {
    $sink = new SpyRecordSink;
    $acks = [];

    $handler = new ConnectionHandler(
        expectedTokenHash: 'abc1234',
        server: $sink,
        ackWriter: function (string $ack) use (&$acks): void {
            $acks[] = $ack;
        },
    );

    $handler->feed(Payload::frame('[{"t":"query"}]', 'evil999'));

    expect($sink->ingested)->toBe([])   // not forwarded
        ->and($acks)->toBe(['2:OK']);   // but framing was still acked
});

it('gracefully signals an unknown frame version rather than crashing', function () {
    $sink = new SpyRecordSink;
    $unknown = false;

    $handler = new ConnectionHandler(
        expectedTokenHash: 'abc1234',
        server: $sink,
        ackWriter: fn (string $ack) => null,
        onUnknownVersion: function () use (&$unknown): void {
            $unknown = true;
        },
    );

    $handler->feed(Payload::frame('[{"t":"query"}]', 'abc1234', 'v9'));

    expect($unknown)->toBeTrue()
        ->and($sink->ingested)->toBe([]);
});

it('a STATS probe against a dead daemon returns null and never throws', function () {
    $probe = stream_socket_server('tcp://127.0.0.1:0');
    $addr = stream_socket_get_name($probe, false);
    $port = substr($addr, strrpos($addr, ':') + 1);
    fclose($probe);

    $client = new SocketClient('127.0.0.1:'.$port, 'abc1234');

    expect($client->stats())->toBeNull();
});

it('the daemon swallows a STATS responder that explodes mid-reply', function () {
    $sink = new SpyRecordSink;
    $writes = [];

    $handler = new ConnectionHandler(
        expectedTokenHash: 'abc1234',
        server: $sink,
        ackWriter: function (string $bytes) use (&$writes): void {
            $writes[] = $bytes;
        },
        statsResponder: fn (): string => throw new RuntimeException('counters exploded'),
    );

    $handler->feed(Payload::frame('STATS', 'abc1234')); // must not throw

    expect($writes)->toBe(['2:OK']);
});

it('the daemon stats log line can never crash the loop when stdout dies', function () {
    $scheduler = new FakeScheduler;

    $reporter = new StatsReporter(
        new DaemonStats('https://daywatch.example.com', new FrozenClock(1.0)),
        $scheduler,
        fn (string $line) => throw new RuntimeException('broken pipe'),
        60,
    );

    $reporter->start();
    $scheduler->fireAll(10); // several fires, every one throwing — none may escape

    expect($scheduler->pending())->toBeGreaterThan(0); // still armed
});

it('boots the provider and attaches sensors without throwing', function () {
    // Reaching this point means register()/boot() wired everything cleanly.
    expect($this->app->bound(Core::class))->toBeTrue()
        ->and($this->app->make(DaywatchRuntime::class))->toBeInstanceOf(DaywatchRuntime::class);
});

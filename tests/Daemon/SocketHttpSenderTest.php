<?php

declare(strict_types=1);

use Daywatch\Agent\Daemon\HttpResponse;
use Daywatch\Agent\Daemon\SocketHttpSender;
use React\EventLoop\Loop;
use React\Socket\Connector;

function callPrivate(object $object, string $method, mixed ...$args): mixed
{
    $ref = new ReflectionMethod($object, $method);
    $ref->setAccessible(true);

    return $ref->invoke($object, ...$args);
}

function makeSender(): SocketHttpSender
{
    // Constructing a Connector performs no I/O; the private methods under test are pure.
    return new SocketHttpSender(new Connector([], Loop::get()), Loop::get(), 10.0);
}

it('parses an HTTP response status line and body', function () {
    $raw = "HTTP/1.1 202 Accepted\r\nContent-Type: application/json\r\n\r\n{\"accepted\":3}";

    $response = callPrivate(makeSender(), 'parse', $raw);

    expect($response)->toBeInstanceOf(HttpResponse::class)
        ->and($response->status)->toBe(202)
        ->and($response->body)->toBe('{"accepted":3}');
});

it('resolves tls:// for https and tcp:// for http, with default ports', function () {
    $sender = makeSender();

    expect(callPrivate($sender, 'target', 'https://ingest.test/api/ingest'))->toBe('tls://ingest.test:443')
        ->and(callPrivate($sender, 'target', 'http://ingest.test:8080/api'))->toBe('tcp://ingest.test:8080');
});

it('extracts the request path with query string', function () {
    $sender = makeSender();

    expect(callPrivate($sender, 'path', 'https://ingest.test/api/ingest?x=1'))->toBe('/api/ingest?x=1')
        ->and(callPrivate($sender, 'path', 'https://ingest.test'))->toBe('/');
});

it('builds a Connection: close HTTP/1.1 request with a correct Content-Length', function () {
    $body = '{"records":[{"a":1}]}';

    $raw = callPrivate(
        makeSender(),
        'rawRequest',
        'POST',
        '/api/ingest',
        'ingest.test',
        ['Authorization' => 'Bearer dw', 'Content-Encoding' => 'gzip'],
        $body,
    );

    expect($raw)->toStartWith("POST /api/ingest HTTP/1.1\r\n")
        ->and($raw)->toContain("Host: ingest.test\r\n")
        ->and($raw)->toContain("Authorization: Bearer dw\r\n")
        ->and($raw)->toContain("Content-Encoding: gzip\r\n")
        ->and($raw)->toContain('Content-Length: '.strlen($body)."\r\n")
        ->and($raw)->toContain("Connection: close\r\n")
        ->and($raw)->toEndWith("\r\n\r\n".$body);
});

it('rejects an invalid ingest URL through the returned promise (never throws)', function () {
    $rejected = null;

    makeSender()->send('POST', 'not-a-url', [], 'body')->then(
        null,
        function (Throwable $e) use (&$rejected): void {
            $rejected = $e->getMessage();
        },
    );

    expect($rejected)->toContain('invalid ingest URL');
});

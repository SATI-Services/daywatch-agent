<?php

declare(strict_types=1);

use Daywatch\Agent\Sensors\OutgoingRequestSensor;
use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Tests\Support\RecordingClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

it('maps a PSR-7 request/response pair to an outgoing-request record', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $request = new Request('POST', 'https://user:pass@api.stripe.com/v1/charges', [], 'body');
    $response = new Response(200, [], 'ok');

    (new OutgoingRequestSensor($core))->record($request, $response, 1000.0, 1000.25);

    $record = $buffer->all()[0];

    expect($record['v'])->toBe(1)
        ->and($record['t'])->toBe('outgoing-request')
        ->and($record['host'])->toBe('api.stripe.com')
        ->and($record['method'])->toBe('POST')
        ->and($record['status_code'])->toBe(200)
        ->and($record['_group'])->toBe(Group::host('api.stripe.com'))
        ->and($record['trace_id'])->toBe($core->traceId)
        ->and($record['execution_source'])->toBe('request');
});

it('strips userinfo from the recorded url', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $request = new Request('POST', 'https://user:pass@api.stripe.com/v1/charges', [], 'body');

    (new OutgoingRequestSensor($core))->record($request, new Response(200), 1000.0, 1000.1);

    $url = $buffer->all()[0]['url'];

    expect($url)->not->toContain('user:pass@')
        ->and($url)->not->toContain('pass')
        ->and($url)->toBe('https://api.stripe.com/v1/charges');
});

it('records a positive duration for a start/end gap', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new OutgoingRequestSensor($core))->record(
        new Request('GET', 'https://api.stripe.com/v1/charges'),
        new Response(200),
        1000.0,
        1000.25, // 250ms
    );

    expect($buffer->all()[0]['duration'])->toBe(250_000);
});

it('records request and response body sizes', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new OutgoingRequestSensor($core))->record(
        new Request('POST', 'https://api.stripe.com/v1/charges', [], 'body'),
        new Response(200, [], 'ok'),
        1000.0,
        1000.1,
    );

    $record = $buffer->all()[0];

    expect($record['request_size'])->toBe(4)  // 'body'
        ->and($record['response_size'])->toBe(2); // 'ok'
});

it('increments the outgoing_requests counter', function () {
    $client = new RecordingClient;
    [$core] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    (new OutgoingRequestSensor($core))->record(
        new Request('GET', 'https://api.stripe.com/v1/charges'),
        new Response(200),
        1000.0,
        1000.1,
    );

    expect($core->counters()['outgoing_requests'])->toBe(1);
});

it('never throws when the request object misbehaves', function () {
    $client = new RecordingClient;
    [$core, $buffer] = makeCore($client, requestRate: 1.0);
    $core->prepareForRequest();

    $request = new class implements RequestInterface
    {
        public function getUri(): UriInterface
        {
            throw new RuntimeException('boom');
        }

        public function getMethod(): string
        {
            return 'GET';
        }

        // Unused interface methods.
        public function getRequestTarget(): string
        {
            return '';
        }

        public function withRequestTarget($requestTarget): static
        {
            return $this;
        }

        public function withMethod($method): static
        {
            return $this;
        }

        public function withUri(UriInterface $uri, $preserveHost = false): static
        {
            return $this;
        }

        public function getProtocolVersion(): string
        {
            return '1.1';
        }

        public function withProtocolVersion($version): static
        {
            return $this;
        }

        public function getHeaders(): array
        {
            return [];
        }

        public function hasHeader($name): bool
        {
            return false;
        }

        public function getHeader($name): array
        {
            return [];
        }

        public function getHeaderLine($name): string
        {
            return '';
        }

        public function withHeader($name, $value): static
        {
            return $this;
        }

        public function withAddedHeader($name, $value): static
        {
            return $this;
        }

        public function withoutHeader($name): static
        {
            return $this;
        }

        public function getBody(): StreamInterface
        {
            throw new RuntimeException('boom');
        }

        public function withBody(StreamInterface $body): static
        {
            return $this;
        }
    };

    (new OutgoingRequestSensor($core))->record($request, new Response(200), 1000.0, 1000.1);

    expect($buffer->all())->toBe([]);
});

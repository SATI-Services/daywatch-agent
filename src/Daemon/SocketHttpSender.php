<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Daemon\Contracts\HttpSender;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;
use RuntimeException;
use Throwable;

/**
 * The daemon's outbound HTTP transport, implemented as a minimal HTTP/1.1 client
 * over react/socket (tls:// for https). This deliberately avoids react/http:
 * every reactphp/http release hard-pins psr/http-message ^1.0, which would
 * downgrade / conflict with a Laravel 13 host on psr/http-message ^2.0. react/socket
 * carries no psr/http-message dependency, so the package stays installable everywhere.
 *
 * A single `Connection: close` request/response cycle — no keep-alive, no chunked
 * framing to unpack — is all the ingest POST needs.
 */
final class SocketHttpSender implements HttpSender
{
    public function __construct(
        private readonly ConnectorInterface $connector,
        private readonly LoopInterface $loop,
        private readonly float $timeout = 10.0,
    ) {}

    public function send(string $method, string $url, array $headers, string $body): PromiseInterface
    {
        $deferred = new Deferred;

        try {
            $target = $this->target($url);
            $path = $this->path($url);
            $host = $headers['Host'] ?? $this->host($url);
        } catch (Throwable $e) {
            $deferred->reject($e);

            return $deferred->promise();
        }

        $settled = false;
        $settle = function (callable $action) use (&$settled): void {
            if (! $settled) {
                $settled = true;
                $action();
            }
        };

        $timer = $this->loop->addTimer($this->timeout, function () use ($settle, $deferred): void {
            $settle(fn () => $deferred->reject(new RuntimeException('ingest request timed out')));
        });

        $this->connector->connect($target)->then(
            function (ConnectionInterface $conn) use ($method, $path, $host, $headers, $body, $deferred, $settle, $timer): void {
                $conn->write($this->rawRequest($method, $path, $host, $headers, $body));

                $raw = '';
                $conn->on('data', function (string $chunk) use (&$raw): void {
                    $raw .= $chunk;
                });

                $finish = function () use (&$raw, $deferred, $settle, $timer, $conn): void {
                    $this->loop->cancelTimer($timer);
                    $settle(fn () => $deferred->resolve($this->parse($raw)));
                    $conn->close();
                };

                $conn->on('end', $finish);
                $conn->on('close', $finish);
                $conn->on('error', function (Throwable $e) use ($deferred, $settle, $timer): void {
                    $this->loop->cancelTimer($timer);
                    $settle(fn () => $deferred->reject($e));
                });
            },
            function (Throwable $e) use ($deferred, $settle, $timer): void {
                $this->loop->cancelTimer($timer);
                $settle(fn () => $deferred->reject($e));
            },
        );

        return $deferred->promise();
    }

    private function rawRequest(string $method, string $path, string $host, array $headers, string $body): string
    {
        $lines = [strtoupper($method).' '.$path.' HTTP/1.1'];
        $lines[] = 'Host: '.$host;

        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Host') === 0) {
                continue;
            }
            $lines[] = $name.': '.$value;
        }

        $lines[] = 'Content-Length: '.strlen($body);
        $lines[] = 'Connection: close';

        return implode("\r\n", $lines)."\r\n\r\n".$body;
    }

    private function parse(string $raw): HttpResponse
    {
        $split = strpos($raw, "\r\n\r\n");
        $headerBlock = $split === false ? $raw : substr($raw, 0, $split);
        $body = $split === false ? '' : substr($raw, $split + 4);

        $status = 0;
        $firstLine = strtok($headerBlock, "\r\n");

        if ($firstLine !== false && preg_match('#^HTTP/\d\.\d\s+(\d{3})#', $firstLine, $m) === 1) {
            $status = (int) $m[1];
        }

        return new HttpResponse($status, $body);
    }

    private function target(string $url): string
    {
        $parts = $this->parts($url);
        $scheme = $parts['scheme'];
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $prefix = $scheme === 'https' ? 'tls://' : 'tcp://';

        return $prefix.$parts['host'].':'.$port;
    }

    private function path(string $url): string
    {
        $parts = $this->parts($url);
        $path = $parts['path'] ?? '/';

        if (isset($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        return $path === '' ? '/' : $path;
    }

    private function host(string $url): string
    {
        return $this->parts($url)['host'];
    }

    /** @return array{scheme:string, host:string, port?:int, path?:string, query?:string} */
    private function parts(string $url): array
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException('invalid ingest URL: '.$url);
        }

        /** @var array{scheme:string, host:string, port?:int, path?:string, query?:string} $parts */
        return $parts;
    }
}

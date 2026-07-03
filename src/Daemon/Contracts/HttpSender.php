<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon\Contracts;

use Daywatch\Agent\Daemon\HttpResponse;
use Daywatch\Agent\Daemon\SocketHttpSender;
use React\Promise\PromiseInterface;

/**
 * The daemon's outbound HTTP transport. Production is {@see SocketHttpSender}
 * (raw HTTP/1.1 over react/socket — deliberately NOT react/http, which would pin
 * psr/http-message < 2.0 and break a Laravel 13 host). Tests stub this with
 * resolved/rejected promises so the retry/pause logic is exercised with no I/O.
 */
interface HttpSender
{
    /**
     * @param  array<string, string>  $headers
     * @return PromiseInterface<HttpResponse> rejects on network error
     */
    public function send(string $method, string $url, array $headers, string $body): PromiseInterface;
}

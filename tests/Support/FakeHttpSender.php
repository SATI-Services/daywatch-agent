<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests\Support;

use Daywatch\Agent\Daemon\Contracts\HttpSender;
use Daywatch\Agent\Daemon\HttpResponse;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Throwable;

use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Deterministic {@see HttpSender} test double. Each call pops the next scripted
 * outcome: an {@see HttpResponse} (resolves), a {@see Throwable} (rejects — network
 * error), or the string 'pending' (returns a never-settling promise, to hold an
 * in-flight slot open). Records every request for assertion.
 */
final class FakeHttpSender implements HttpSender
{
    /** @var list<array{method: string, url: string, headers: array<string,string>, body: string}> */
    public array $sent = [];

    /** @var list<HttpResponse|Throwable|string> */
    private array $queue;

    public function __construct(
        array $queue = [],
        private readonly HttpResponse|Throwable|string|null $default = null,
    ) {
        $this->queue = $queue;
    }

    public function send(string $method, string $url, array $headers, string $body): PromiseInterface
    {
        $this->sent[] = compact('method', 'url', 'headers', 'body');

        $next = array_shift($this->queue) ?? $this->default;

        if ($next === null || $next === 'pending') {
            return (new Deferred)->promise();
        }

        if ($next instanceof Throwable) {
            return reject($next);
        }

        return resolve($next);
    }

    public function count(): int
    {
        return count($this->sent);
    }

    /** @return array{method: string, url: string, headers: array<string,string>, body: string} */
    public function last(): array
    {
        return $this->sent[array_key_last($this->sent)];
    }
}

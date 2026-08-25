<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Daemon\Contracts\HttpSender;
use Daywatch\Agent\Support\Uuid;
use React\Promise\PromiseInterface;
use Throwable;

use function React\Promise\resolve;

/**
 * The daemon's startup authentication check, run once as `daywatch:agent` boots.
 *
 * It POSTs an EMPTY batch — `{"records":[]}`, gzipped, with the same headers a
 * real flush carries — to the configured `{base_url}/api/ingest`. That is the one
 * probe both ingest implementations answer identically and conclusively: auth runs
 * before any payload handling in each, and an empty records array is a valid batch
 * that stores nothing (the Laravel app returns `202 {"accepted":0}`; the Java relay
 * returns the same and enqueues no rows). So a 2xx proves the token is accepted, a
 * 401 proves it isn't, and a 404 means `DAYWATCH_BASE_URL` doesn't point at an
 * ingest at all — all before a single record has been buffered.
 *
 * When the token is accepted the probe additionally tries
 * `GET {base_url}/api/ingest/tenancy` to name the project/environment it resolved
 * to. That endpoint exists on the Laravel app (it backs the relay's own token
 * lookup) and not on the relay, so it is strictly best-effort decoration: a 404
 * there never downgrades the headline result.
 *
 * The check is diagnostic ONLY. It never blocks the loop, never rejects, and a
 * failure never stops the daemon — the ingest may simply not be up yet, and the
 * dispatcher has its own retry ladder. CARDINAL RULE: nothing escapes.
 */
final class AuthProbe
{
    /** A valid batch that stores nothing on either ingest implementation. */
    private const EMPTY_BATCH = '{"records":[]}';

    /** @var callable(string): void */
    private $logger;

    /** @var callable(): string */
    private $batchIdFactory;

    public function __construct(
        private readonly HttpSender $sender,
        private readonly string $url,
        private readonly string $token,
        private readonly string $server = '',
        private readonly string $userAgent = 'DaywatchAgent/dev',
        private readonly ?DaemonStats $stats = null,
        ?callable $logger = null,
        ?callable $batchIdFactory = null,
    ) {
        $this->logger = $logger ?? static fn (string $m) => null;
        $this->batchIdFactory = $batchIdFactory ?? static fn (): string => Uuid::v4();
    }

    /**
     * Run the check. The returned promise always RESOLVES with a result —
     * transport rejections are folded into an `unreachable` outcome.
     *
     * @return PromiseInterface<AuthProbeResult>
     */
    public function run(): PromiseInterface
    {
        try {
            if (trim($this->token) === '') {
                return resolve($this->settle(new AuthProbeResult(AuthProbeResult::MISSING_TOKEN)));
            }

            return $this->preflight()->then(function (AuthProbeResult $result): PromiseInterface {
                return $result->ok()
                    ? $this->describeTenancy($result)->then(fn (AuthProbeResult $r) => $this->settle($r))
                    : resolve($this->settle($result));
            });
        } catch (Throwable $e) {
            return resolve($this->settle(new AuthProbeResult(AuthProbeResult::UNREACHABLE, $e->getMessage())));
        }
    }

    /** POST the empty batch and classify the answer. Never rejects. */
    private function preflight(): PromiseInterface
    {
        [$body, $encoding] = $this->body();

        $headers = array_filter([
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type' => 'application/json',
            'Content-Encoding' => $encoding,
            'Accept' => 'application/json',
            'Daywatch-Server' => $this->server,
            'Daywatch-Batch-Id' => ($this->batchIdFactory)(),
            'User-Agent' => $this->userAgent,
        ], static fn (string $value) => $value !== '');

        return $this->sender->send('POST', $this->url, $headers, $body)->then(
            fn (HttpResponse $response) => $this->classify($response),
            fn (Throwable $e) => new AuthProbeResult(AuthProbeResult::UNREACHABLE, $e->getMessage()),
        );
    }

    /**
     * Best-effort enrichment: name the tenancy the token resolved to. Anything
     * other than a 200 with a usable body leaves the result exactly as it was.
     */
    private function describeTenancy(AuthProbeResult $result): PromiseInterface
    {
        try {
            return $this->sender->send('GET', rtrim($this->url, '/').'/tenancy', [
                'Authorization' => 'Bearer '.$this->token,
                'Accept' => 'application/json',
                'User-Agent' => $this->userAgent,
            ], '')->then(
                function (HttpResponse $response) use ($result): AuthProbeResult {
                    if ($response->status < 200 || $response->status >= 300) {
                        return $result;
                    }

                    $body = $response->json();

                    return $result->withTenancy(
                        $this->stringOrNull($body['project_id'] ?? null),
                        $this->stringOrNull($body['environment'] ?? null),
                    );
                },
                static fn (Throwable $e) => $result,
            );
        } catch (Throwable) {
            return resolve($result);
        }
    }

    private function classify(HttpResponse $response): AuthProbeResult
    {
        $status = $response->status;

        if ($status >= 200 && $status < 300) {
            return new AuthProbeResult(AuthProbeResult::OK, status: $status);
        }

        if ($status === 401 || $status === 403) {
            return new AuthProbeResult(AuthProbeResult::UNAUTHORIZED, status: $status);
        }

        if ($status === 404) {
            return new AuthProbeResult(AuthProbeResult::NOT_FOUND, status: $status);
        }

        // 429/503/5xx: the ingest is up but shedding (the relay also answers 429
        // when its own tenancy directory is unavailable), so the token stays
        // unproven — the dispatcher's retry ladder will settle it.
        return new AuthProbeResult(
            AuthProbeResult::UNVERIFIED,
            $this->message($response),
            $status,
        );
    }

    /**
     * Gzip the probe body when zlib is present; both ingests accept plain JSON.
     *
     * @return array{0: string, 1: string} [body, Content-Encoding]
     */
    private function body(): array
    {
        if (function_exists('gzencode')) {
            $gzip = @gzencode(self::EMPTY_BATCH, 6);

            if ($gzip !== false) {
                return [$gzip, 'gzip'];
            }
        }

        return [self::EMPTY_BATCH, ''];
    }

    /** The ingest's own `message`, when it sent one (kept short for one log line). */
    private function message(HttpResponse $response): string
    {
        $message = $response->json()['message'] ?? null;

        return is_scalar($message) ? substr((string) $message, 0, 120) : '';
    }

    /** Record + log the outcome, guarded, and hand it back. */
    private function settle(AuthProbeResult $result): AuthProbeResult
    {
        try {
            $this->stats?->authProbed($result->state, $result->summary());
        } catch (Throwable) {
        }

        try {
            ($this->logger)($result->logLine());

            if ($result->isAuthFailure()) {
                ($this->logger)('[daywatch:agent] telemetry will be dropped until the ingest accepts this agent');
            }
        } catch (Throwable) {
        }

        return $result;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

/**
 * The outcome of the daemon's startup authentication check ({@see AuthProbe}).
 *
 * Deliberately richer than a bool: an operator needs to tell "my token is wrong"
 * (fix `DAYWATCH_TOKEN`) apart from "nothing is listening" (start the ingest)
 * apart from "wrong URL" (fix `DAYWATCH_BASE_URL`) apart from "the ingest is up
 * and authenticated me but is currently shedding load" (nothing to fix).
 */
final class AuthProbeResult
{
    /** Not run yet (or still in flight). */
    public const PENDING = 'pending';

    /** The ingest accepted the token — telemetry will be authorised. */
    public const OK = 'ok';

    /** 401/403: the ingest rejected this token. Nothing will be accepted. */
    public const UNAUTHORIZED = 'unauthorized';

    /** No token configured — nothing to check, and every batch would 401. */
    public const MISSING_TOKEN = 'missing_token';

    /** 404: something answered, but there is no ingest endpoint at that URL. */
    public const NOT_FOUND = 'not_found';

    /** Reachable, past nothing conclusive: 429/503/5xx — retry-shaped, token unproven. */
    public const UNVERIFIED = 'unverified';

    /** Never got an answer: DNS, refused connection, TLS, timeout. */
    public const UNREACHABLE = 'unreachable';

    public function __construct(
        public readonly string $state,
        public readonly string $detail = '',
        public readonly int $status = 0,
        public readonly ?string $projectId = null,
        public readonly ?string $environment = null,
    ) {}

    public function ok(): bool
    {
        return $this->state === self::OK;
    }

    /** True when telemetry will definitely be rejected until an operator acts. */
    public function isAuthFailure(): bool
    {
        return $this->state === self::UNAUTHORIZED
            || $this->state === self::MISSING_TOKEN
            || $this->state === self::NOT_FOUND;
    }

    /** Copy of this result carrying the tenancy the ingest resolved the token to. */
    public function withTenancy(?string $projectId, ?string $environment): self
    {
        return new self($this->state, $this->detail, $this->status, $projectId, $environment);
    }

    /** Compact one-liner for the dashboard's auth row and the boot log. */
    public function summary(): string
    {
        return match ($this->state) {
            self::PENDING => 'checking…',
            self::OK => 'ok · token accepted'.($this->tenancy() === '' ? '' : ' · '.$this->tenancy()),
            self::UNAUTHORIZED => 'REJECTED · token not accepted ('.$this->status.') — check DAYWATCH_TOKEN',
            self::MISSING_TOKEN => 'NOT CONFIGURED · DAYWATCH_TOKEN is empty',
            self::NOT_FOUND => 'NOT FOUND · no ingest endpoint (404) — check DAYWATCH_BASE_URL',
            self::UNVERIFIED => 'unverified · ingest answered '.$this->status
                .($this->detail === '' ? '' : ' ('.$this->detail.')'),
            default => 'UNREACHABLE · '.($this->detail === '' ? 'no answer' : $this->detail),
        };
    }

    /** The supervisor-visible startup line. */
    public function logLine(): string
    {
        return '[daywatch:agent] auth '.$this->summary();
    }

    private function tenancy(): string
    {
        $parts = [];

        if ($this->projectId !== null && $this->projectId !== '') {
            $parts[] = 'project '.$this->projectId;
        }

        if ($this->environment !== null && $this->environment !== '') {
            $parts[] = $this->environment;
        }

        return implode(' · ', $parts);
    }
}

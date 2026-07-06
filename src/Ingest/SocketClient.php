<?php

declare(strict_types=1);

namespace Daywatch\Agent\Ingest;

use Throwable;

/**
 * Short-lived blocking TCP client used once per digest (daywatch-mcp/docs/agent-protocol.md
 * §4). Connects with a 0.5 s connect timeout, writes one frame, awaits the
 * `2:OK` ack under a 0.5 s read timeout, closes.
 *
 * CARDINAL RULE: every failure mode — dead daemon, refused connection, timeout,
 * socket closed mid-write, wrong ack — is caught and returns false. Nothing here
 * may throw into the host app. Worst case ≈ connect timeout + read timeout ≈ 1 s.
 */
class SocketClient implements Client
{
    /** Ceiling on a STATS reply body — the counters JSON is a few hundred bytes. */
    private const MAX_STATS_REPLY_BYTES = 65_536;

    /** Process-level circuit breaker: suppress attempts until this wall-clock time. */
    private float $openUntil = 0.0;

    /** Count of actual connect() attempts — the breaker's effect is observable here. */
    public int $connectionAttempts = 0;

    /**
     * @param  float  $failureCooldown  Seconds to short-circuit after a failed
     *                                  attempt (0 disables the breaker). Bounds per-execution cost when the
     *                                  daemon is persistently unreachable or hung; a healthy daemon resets it.
     */
    public function __construct(
        private string $uri,
        private string $tokenHash,
        private float $connectionTimeout = 0.5,
        private float $timeout = 0.5,
        private float $failureCooldown = 0.0,
    ) {}

    public function send(string $payload): bool
    {
        return $this->transmit($payload);
    }

    public function ping(): bool
    {
        return $this->transmit(Payload::PING);
    }

    public function stats(): ?array
    {
        $socket = null;

        if ($this->breakerOpen()) {
            return null;
        }

        try {
            $socket = $this->connect();

            if ($socket === false) {
                $this->trip();

                return null;
            }

            if (! $this->writeAll($socket, Payload::frame(Payload::STATS, $this->tokenHash))) {
                $this->trip();

                return null;
            }

            if ($this->readAck($socket) !== Payload::ACK) {
                $this->trip();

                return null;
            }

            $json = $this->readMiniFrame($socket);

            if ($json === null) {
                $this->trip();

                return null;
            }

            $decoded = json_decode($json, true);
            $this->reset();

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            $this->trip();

            return null;
        } finally {
            if (is_resource($socket)) {
                @fclose($socket);
            }
        }
    }

    private function transmit(string $body): bool
    {
        $socket = null;

        if ($this->breakerOpen()) {
            return false;
        }

        try {
            $frame = Payload::frame($body, $this->tokenHash);

            $socket = $this->connect();

            if ($socket === false) {
                $this->trip();

                return false;
            }

            if (! $this->writeAll($socket, $frame)) {
                $this->trip();

                return false;
            }

            $ok = $this->readAck($socket) === Payload::ACK;
            $ok ? $this->reset() : $this->trip();

            return $ok;
        } catch (Throwable) {
            $this->trip();

            return false;
        } finally {
            if (is_resource($socket)) {
                @fclose($socket);
            }
        }
    }

    /** True while the breaker is open (a recent failure is still cooling down). */
    private function breakerOpen(): bool
    {
        return $this->failureCooldown > 0.0 && $this->now() < $this->openUntil;
    }

    /** Open the breaker for the cooldown window after a failed attempt. */
    private function trip(): void
    {
        if ($this->failureCooldown > 0.0) {
            $this->openUntil = $this->now() + $this->failureCooldown;
        }
    }

    /** Close the breaker after a healthy round-trip. */
    private function reset(): void
    {
        $this->openUntil = 0.0;
    }

    /** Wall clock, in seconds — a seam so the breaker is deterministically testable. */
    protected function now(): float
    {
        return microtime(true);
    }

    /** @return resource|false */
    private function connect()
    {
        $this->connectionAttempts++;

        $socket = @stream_socket_client(
            'tcp://'.$this->uri,
            $errno,
            $errstr,
            $this->connectionTimeout,
            STREAM_CLIENT_CONNECT,
        );

        if ($socket === false) {
            return false;
        }

        $seconds = (int) $this->timeout;
        $micros = (int) round(($this->timeout - $seconds) * 1_000_000);
        stream_set_timeout($socket, $seconds, $micros);

        return $socket;
    }

    /** @param  resource  $socket */
    private function writeAll($socket, string $frame): bool
    {
        $length = strlen($frame);
        $written = 0;

        while ($written < $length) {
            $bytes = @fwrite($socket, substr($frame, $written));

            if ($bytes === false || $bytes === 0) {
                return false;
            }

            $written += $bytes;

            $meta = stream_get_meta_data($socket);
            if (! empty($meta['timed_out'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read one `{length}:{payload}` mini-frame (the shape the ack itself uses) —
     * the daemon's STATS counters reply. Returns null on timeout, EOF, a
     * malformed length prefix, or an implausible declared length.
     *
     * @param  resource  $socket
     */
    private function readMiniFrame($socket): ?string
    {
        $prefix = '';
        $sawColon = false;

        // The length prefix: bounded run of digits terminated by ':'.
        while (strlen($prefix) <= 10) {
            $char = @fread($socket, 1);

            if ($char === false || $char === '' || $this->timedOut($socket)) {
                return null;
            }

            if ($char === ':') {
                $sawColon = true;

                break;
            }

            if (! ctype_digit($char)) {
                return null;
            }

            $prefix .= $char;
        }

        if (! $sawColon || $prefix === '') {
            return null;
        }

        $length = (int) $prefix;

        if ($length < 1 || $length > self::MAX_STATS_REPLY_BYTES) {
            return null;
        }

        $body = '';

        while (strlen($body) < $length) {
            $chunk = @fread($socket, $length - strlen($body));

            if ($chunk === false || $chunk === '') {
                return null;
            }

            $body .= $chunk;

            if (strlen($body) < $length && $this->timedOut($socket)) {
                return null;
            }
        }

        return $body;
    }

    /** @param  resource  $socket */
    private function timedOut($socket): bool
    {
        $meta = stream_get_meta_data($socket);

        return ! empty($meta['timed_out']);
    }

    /** @param  resource  $socket */
    private function readAck($socket): string
    {
        $ack = '';

        // Ack is the fixed 4-byte string "2:OK".
        while (strlen($ack) < strlen(Payload::ACK)) {
            $chunk = @fread($socket, strlen(Payload::ACK) - strlen($ack));

            if ($chunk === false || $chunk === '') {
                break;
            }

            $ack .= $chunk;

            $meta = stream_get_meta_data($socket);
            if (! empty($meta['timed_out'])) {
                break;
            }
        }

        return $ack;
    }
}

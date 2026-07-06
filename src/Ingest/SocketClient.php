<?php

declare(strict_types=1);

namespace Daywatch\Agent\Ingest;

use Throwable;

/**
 * Short-lived blocking TCP client used once per digest (services/daywatch-mcp/docs/agent-protocol.md
 * §4). Connects with a 0.5 s connect timeout, writes one frame, awaits the
 * `2:OK` ack under a 0.5 s read timeout, closes.
 *
 * CARDINAL RULE: every failure mode — dead daemon, refused connection, timeout,
 * socket closed mid-write, wrong ack — is caught and returns false. Nothing here
 * may throw into the host app. Worst case ≈ connect timeout + read timeout ≈ 1 s.
 */
final class SocketClient implements Client
{
    /** Ceiling on a STATS reply body — the counters JSON is a few hundred bytes. */
    private const MAX_STATS_REPLY_BYTES = 65_536;

    public function __construct(
        private string $uri,
        private string $tokenHash,
        private float $connectionTimeout = 0.5,
        private float $timeout = 0.5,
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

        try {
            $socket = $this->connect();

            if ($socket === false) {
                return null;
            }

            if (! $this->writeAll($socket, Payload::frame(Payload::STATS, $this->tokenHash))) {
                return null;
            }

            if ($this->readAck($socket) !== Payload::ACK) {
                return null;
            }

            $json = $this->readMiniFrame($socket);

            if ($json === null) {
                return null;
            }

            $decoded = json_decode($json, true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
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

        try {
            $frame = Payload::frame($body, $this->tokenHash);

            $socket = $this->connect();

            if ($socket === false) {
                return false;
            }

            if (! $this->writeAll($socket, $frame)) {
                return false;
            }

            return $this->readAck($socket) === Payload::ACK;
        } catch (Throwable) {
            return false;
        } finally {
            if (is_resource($socket)) {
                @fclose($socket);
            }
        }
    }

    /** @return resource|false */
    private function connect()
    {
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

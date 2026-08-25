<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

/**
 * Incremental, per-connection parser for the length-prefixed local socket frame
 * (agent protocol §4):
 *
 *   {length}:{version}:{token_hash}:{payload}
 *
 * `length` is the byte length of everything after the FIRST colon. Multiple
 * frames may be pipelined on one connection; a frame may arrive across several
 * chunks. This class never throws — malformed framing sets an unrecoverable
 * error flag (the caller drops the connection) rather than blowing up the loop.
 */
final class FrameParser
{
    /** Hard ceiling on a single declared frame length (matches the 64 MB inflated batch cap). */
    public const MAX_FRAME_BYTES = 67_108_864;

    private string $buffer = '';

    private bool $errored = false;

    private ?string $errorMessage = null;

    public function __construct(private readonly int $maxFrameBytes = self::MAX_FRAME_BYTES) {}

    /**
     * Feed raw bytes; return every complete frame now available.
     *
     * @return list<Frame>
     */
    public function push(string $chunk): array
    {
        if ($this->errored) {
            return [];
        }

        $this->buffer .= $chunk;
        $frames = [];

        while (true) {
            $colon = strpos($this->buffer, ':');

            if ($colon === false) {
                // No length delimiter yet. Guard against an unbounded run of
                // non-numeric junk masquerading as a length prefix.
                if (strlen($this->buffer) > 20) {
                    return $this->fail('frame length prefix too long / not numeric');
                }

                break;
            }

            $lengthToken = substr($this->buffer, 0, $colon);

            if ($lengthToken === '' || ! ctype_digit($lengthToken)) {
                return $this->fail('non-numeric frame length prefix');
            }

            $length = (int) $lengthToken;

            if ($length > $this->maxFrameBytes) {
                return $this->fail('frame length exceeds maximum');
            }

            $restStart = $colon + 1;

            if (strlen($this->buffer) - $restStart < $length) {
                // The declared body has not fully arrived yet.
                break;
            }

            $rest = substr($this->buffer, $restStart, $length);
            $this->buffer = substr($this->buffer, $restStart + $length);

            $frame = $this->decode($rest);

            if ($frame !== null) {
                $frames[] = $frame;
            }
        }

        return $frames;
    }

    public function errored(): bool
    {
        return $this->errored;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /** Split `{version}:{token_hash}:{payload}` on the first two colons only. */
    private function decode(string $rest): ?Frame
    {
        $first = strpos($rest, ':');

        if ($first === false) {
            return null;
        }

        $second = strpos($rest, ':', $first + 1);

        if ($second === false) {
            return null;
        }

        return new Frame(
            version: substr($rest, 0, $first),
            tokenHash: substr($rest, $first + 1, $second - $first - 1),
            payload: substr($rest, $second + 1),
        );
    }

    /**
     * @return list<Frame> always empty — framing is unrecoverable from here.
     */
    private function fail(string $message): array
    {
        $this->errored = true;
        $this->errorMessage = $message;
        $this->buffer = '';

        return [];
    }
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Daemon;

use Daywatch\Agent\Daemon\Contracts\RecordSink;
use Daywatch\Agent\Ingest\Payload;
use Throwable;

/**
 * Per-connection frame processing (services/daywatch-mcp/docs/agent-protocol.md §4). Feeds raw bytes
 * through a {@see FrameParser}; for each complete frame it ACKs `2:OK` the moment
 * the declared length is satisfied, then processes: unknown version → graceful
 * final digest + shutdown; token mismatch → log + drop; PING → ack only; STATS →
 * ack then one `{length}:{json}` counters reply; a record array → hand to the
 * {@see IngestServer}. Malformed framing closes the connection.
 *
 * Everything is guarded — a hostile or corrupt client can never crash the daemon.
 */
final class ConnectionHandler
{
    private readonly FrameParser $parser;

    /** @var callable(string): void */
    private $ackWriter;

    /** @var callable(): void */
    private $onUnknownVersion;

    /** @var callable(string): void */
    private $onError;

    /** @var callable(string): void */
    private $logger;

    /** @var callable(): ?string */
    private $statsResponder;

    public function __construct(
        private readonly string $expectedTokenHash,
        private readonly RecordSink $server,
        callable $ackWriter,
        ?callable $onUnknownVersion = null,
        ?callable $onError = null,
        ?callable $logger = null,
        ?callable $statsResponder = null,
    ) {
        $this->parser = new FrameParser;
        $this->ackWriter = $ackWriter;
        $this->onUnknownVersion = $onUnknownVersion ?? static fn () => null;
        $this->onError = $onError ?? static fn (string $m) => null;
        $this->logger = $logger ?? static fn (string $m) => null;
        $this->statsResponder = $statsResponder ?? static fn (): ?string => null;
    }

    public function feed(string $chunk): void
    {
        try {
            $frames = $this->parser->push($chunk);

            foreach ($frames as $frame) {
                // Ack framing receipt immediately, regardless of auth/version.
                ($this->ackWriter)(Payload::ACK);

                $this->process($frame);
            }

            if ($this->parser->errored()) {
                ($this->onError)((string) $this->parser->errorMessage());
            }
        } catch (Throwable $e) {
            $this->log('connection feed failed: '.$e->getMessage());
            ($this->onError)('exception');
        }
    }

    private function process(Frame $frame): void
    {
        if ($frame->version !== Payload::VERSION) {
            $this->log('unknown frame version "'.$frame->version.'" — final digest then graceful exit');
            ($this->onUnknownVersion)();

            return;
        }

        if (! hash_equals($this->expectedTokenHash, $frame->tokenHash)) {
            $this->log('token hash mismatch — dropping payload');

            return;
        }

        if ($frame->isPing()) {
            return; // reachability probe: the ack above is the whole response
        }

        if ($frame->isStats()) {
            $this->respondWithStats();

            return;
        }

        $this->server->ingest($frame->payload);
    }

    /**
     * Write the STATS counters reply as one `{length}:{json}` mini-frame — the
     * same shape as the ack (services/daywatch-mcp/docs/agent-protocol.md §4). Guarded: a responder or
     * write failure is logged and swallowed, never thrown into the loop.
     */
    private function respondWithStats(): void
    {
        try {
            $json = ($this->statsResponder)();

            if (is_string($json) && $json !== '') {
                ($this->ackWriter)(strlen($json).':'.$json);
            }
        } catch (Throwable $e) {
            $this->log('stats reply failed: '.$e->getMessage());
        }
    }

    private function log(string $message): void
    {
        try {
            ($this->logger)('[daywatch:agent] '.$message);
        } catch (Throwable) {
        }
    }
}

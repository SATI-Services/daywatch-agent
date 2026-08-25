<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Truncate;

/**
 * The `log` record (daywatch-mcp/docs/agent-protocol.md §2). **No `_group`.** `message` has
 * its placeholders interpolated; `context` and `extra` are JSON strings.
 *
 * Field names are WIRE CONTRACT.
 */
final class LogRecord
{
    public function __construct(
        public Envelope $envelope,
        public string $level,
        public string $message,
        public string $context,
        public string $extra,
    ) {}

    public function toArray(): array
    {
        return $this->envelope->child('log') + [
            'level' => Truncate::tiny($this->level),
            'message' => Truncate::text($this->message),
            'context' => Truncate::text($this->context),
            'extra' => Truncate::text($this->extra),
        ];
    }
}

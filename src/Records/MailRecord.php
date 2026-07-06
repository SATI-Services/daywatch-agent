<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `mail` record (daywatch-mcp/docs/agent-protocol.md §2) — a message sent through a
 * mailer (MessageSending→MessageSent). `to`/`cc`/`bcc`/`attachments` are integer
 * COUNTS — recipient addresses are never transmitted. `_group = xxh128(class)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class MailRecord
{
    public function __construct(
        public float $timestamp,
        public string $deploy,
        public string $server,
        public string $traceId,
        public string $executionId,
        public string $executionSource,
        public string $executionPreview,
        public string $executionStage,
        public string $user,
        public string $mailer,
        public string $class,
        public string $subject,
        public int $to,
        public int $cc,
        public int $bcc,
        public int $attachments,
        public int $duration,
        public bool $failed,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'mail',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::name($this->class),
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
            'mailer' => Truncate::tiny($this->mailer),
            'class' => Truncate::tiny($this->class),
            'subject' => Truncate::tiny($this->subject),
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'attachments' => $this->attachments,
            'duration' => $this->duration,
            'failed' => $this->failed,
        ];
    }
}

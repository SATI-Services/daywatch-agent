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
        public Envelope $envelope,
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
        return $this->envelope->child('mail', Group::name($this->class)) + [
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

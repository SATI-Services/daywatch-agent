<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\MailRecord;
use Throwable;

/**
 * MailSensor — pairs MessageSending → MessageSent into a `mail` record
 * (daywatch-mcp/docs/agent-protocol.md §2). The Sending timestamp is stashed
 * keyed by the message's object id so Sent can compute the send duration.
 *
 * `to`/`cc`/`bcc`/`attachments` are integer COUNTS only — recipient addresses
 * are never read or transmitted. Every accessor is guarded; this sensor never
 * throws into the host app (CARDINAL RULE).
 */
final class MailSensor
{
    /** @var array<int, float> spl_object_id($message) → Sending microtime */
    private array $pending = [];

    public function __construct(private Core $core) {}

    public function sending(object $event): void
    {
        try {
            $message = $event->message ?? null;

            if (! is_object($message)) {
                return;
            }

            $this->pending[spl_object_id($message)] = $this->core->clock()->microtime();
        } catch (Throwable) {
            // telemetry loss is acceptable; mail must never fail loudly
        }
    }

    public function sent(object $event): void
    {
        try {
            $core = $this->core;
            $message = $event->message ?? null;

            if (! is_object($message)) {
                return;
            }

            $now = $core->clock()->microtime();
            $id = spl_object_id($message);
            $start = $this->pending[$id] ?? $now;
            unset($this->pending[$id]);

            $duration = (int) round(($now - $start) * 1_000_000);

            if ($duration < 0) {
                $duration = 0;
            }

            $data = is_array($event->data ?? null) ? $event->data : [];

            $record = new MailRecord(
                envelope: Envelope::for($core, $start),
                mailer: (string) ($data['mailer'] ?? ''),
                class: (string) ($data['__laravel_mailable'] ?? ''),
                subject: $this->subject($message),
                to: $this->recipientCount($message, 'getTo'),
                cc: $this->recipientCount($message, 'getCc'),
                bcc: $this->recipientCount($message, 'getBcc'),
                attachments: $this->attachmentCount($message),
                duration: $duration,
                failed: false,
            );

            $core->recordMail($record->toArray());
        } catch (Throwable) {
            // telemetry loss is acceptable; mail must never fail loudly
        }
    }

    private function subject(object $message): string
    {
        try {
            if (method_exists($message, 'getSubject')) {
                return (string) ($message->getSubject() ?? '');
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function recipientCount(object $message, string $method): int
    {
        try {
            if (method_exists($message, $method)) {
                $recipients = $message->{$method}();

                return is_countable($recipients) ? count($recipients) : 0;
            }
        } catch (Throwable) {
        }

        return 0;
    }

    private function attachmentCount(object $message): int
    {
        try {
            if (method_exists($message, 'getAttachments')) {
                $attachments = $message->getAttachments();

                return is_countable($attachments) ? count($attachments) : 0;
            }
        } catch (Throwable) {
        }

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `notification` record (agent protocol §2) — a notification sent on
 * a channel (NotificationSending→NotificationSent). `_group = xxh128(class)`.
 *
 * Field names are WIRE CONTRACT.
 */
final class NotificationRecord
{
    public function __construct(
        public Envelope $envelope,
        public string $channel,
        public string $class,
        public int $duration,
        public bool $failed,
    ) {}

    public function toArray(): array
    {
        return $this->envelope->child('notification', Group::name($this->class)) + [
            'channel' => Truncate::tiny($this->channel),
            'class' => Truncate::tiny($this->class),
            'duration' => $this->duration,
            'failed' => $this->failed,
        ];
    }
}

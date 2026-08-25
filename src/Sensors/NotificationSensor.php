<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\NotificationRecord;
use Throwable;

/**
 * NotificationSensor — pairs NotificationSending → NotificationSent into a
 * `notification` record (agent protocol §2). The Sending
 * timestamp is stashed keyed by the notification's object id so the Sent event
 * can compute an integer-microsecond duration; an unpaired Sent yields 0.
 * `_group = xxh128(class)`. `failed` is always false — Laravel dispatches no
 * NotificationFailed event to observe.
 *
 * CARDINAL RULE: every accessor is guarded and the whole path is try/caught —
 * this sensor never throws into the host app.
 */
final class NotificationSensor
{
    /**
     * Sending timestamps keyed by spl_object_id of the notification.
     *
     * @var array<int, float>
     */
    private array $pending = [];

    public function __construct(private Core $core) {}

    public function sending(object $event): void
    {
        try {
            $key = $this->key($event);

            if ($key !== null) {
                $this->pending[$key] = $this->core->clock()->microtime();
            }
        } catch (Throwable) {
            // telemetry loss is acceptable; never surface into the host app
        }
    }

    public function sent(object $event): void
    {
        try {
            $core = $this->core;
            $now = $core->clock()->microtime();

            $key = $this->key($event);
            $start = $now;

            if ($key !== null && isset($this->pending[$key])) {
                $start = $this->pending[$key];
                unset($this->pending[$key]);
            }

            $durationUs = (int) round(max(0.0, $now - $start) * 1_000_000);

            $record = new NotificationRecord(
                envelope: Envelope::for($core, $start),
                channel: $this->channel($event),
                class: $this->class($event),
                duration: $durationUs,
                failed: false,
            );

            $core->recordNotification($record->toArray());
        } catch (Throwable) {
            // telemetry loss is acceptable; never surface into the host app
        }
    }

    /**
     * Stable id for pairing Sending/Sent — the notification object if present.
     */
    private function key(object $event): ?int
    {
        $notification = $event->notification ?? null;

        return is_object($notification) ? spl_object_id($notification) : null;
    }

    private function channel(object $event): string
    {
        $channel = $event->channel ?? '';

        return is_string($channel) ? $channel : '';
    }

    private function class(object $event): string
    {
        $notification = $event->notification ?? null;

        return is_object($notification) ? get_class($notification) : '';
    }
}

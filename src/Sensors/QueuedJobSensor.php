<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\QueuedJobRecord;
use Throwable;

/**
 * QueuedJobSensor — pairs `Illuminate\Queue\Events\JobQueueing` and
 * `Illuminate\Queue\Events\JobQueued` into a `queued-job` record
 * (agent protocol §2).
 *
 * The `JobQueueing` timestamp is stashed so the `JobQueued` handler can compute
 * the enqueue duration (integer microseconds; unpaired → 0). `sync` connection
 * jobs are never emitted. `_group = xxh128(name)`.
 *
 * CARDINAL RULE: every handler is un-crashable — a dispatch must never fail
 * loudly because telemetry could not be recorded.
 */
final class QueuedJobSensor
{
    private ?float $queueingAt = null;

    public function __construct(private Core $core) {}

    /** Stash the enqueue start so {@see queued()} can measure the duration. */
    public function queueing(object $event): void
    {
        try {
            $this->queueingAt = $this->core->clock()->microtime();
        } catch (Throwable) {
            $this->queueingAt = null;
        }
    }

    public function queued(object $event): void
    {
        try {
            $core = $this->core;
            $now = $core->clock()->microtime();

            $connection = $this->connection($event);

            // `sync` jobs run inline; there is nothing queued to observe.
            if ($connection === 'sync') {
                $this->queueingAt = null;

                return;
            }

            $duration = $this->queueingAt === null
                ? 0
                : (int) round(($now - $this->queueingAt) * 1_000_000);

            $start = $this->queueingAt ?? $now;

            $record = new QueuedJobRecord(
                envelope: Envelope::for($core, $start),
                jobId: $this->jobId($event),
                name: $this->name($event),
                connection: $connection,
                queue: $this->queue($event),
                duration: max(0, $duration),
            );

            $core->recordQueuedJob($record->toArray());
        } catch (Throwable) {
            // telemetry loss is acceptable; a dispatch must never fail loudly
        } finally {
            $this->queueingAt = null;
        }
    }

    private function connection(object $event): string
    {
        try {
            return (string) ($event->connectionName ?? '');
        } catch (Throwable) {
            return '';
        }
    }

    /** Queue name with any SQS URL prefix stripped (keep the trailing segment). */
    private function queue(object $event): string
    {
        try {
            $queue = $event->queue ?? 'default';

            if (! is_string($queue) || $queue === '') {
                return 'default';
            }

            $slash = strrpos($queue, '/');

            if ($slash !== false) {
                $queue = substr($queue, $slash + 1);
            }

            return $queue === '' ? 'default' : $queue;
        } catch (Throwable) {
            return 'default';
        }
    }

    /** Human-readable job name: displayName() → class → the string itself. */
    private function name(object $event): string
    {
        try {
            $job = $event->job ?? null;

            if (is_string($job)) {
                return $job;
            }

            if (is_object($job)) {
                if (method_exists($job, 'displayName')) {
                    $display = $job->displayName();

                    if (is_string($display) && $display !== '') {
                        return $display;
                    }
                }

                return get_class($job);
            }
        } catch (Throwable) {
        }

        return '';
    }

    /** The Daywatch job id injected into the queue payload, if present. */
    private function jobId(object $event): string
    {
        try {
            if (! method_exists($event, 'payload')) {
                return '';
            }

            $payload = $event->payload();

            if (! is_array($payload)) {
                return '';
            }

            $id = $payload['daywatch']['job_id'] ?? '';

            return is_string($id) ? $id : '';
        } catch (Throwable) {
            return '';
        }
    }
}

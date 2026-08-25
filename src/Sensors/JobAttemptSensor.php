<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Records\JobAttemptRecord;
use Throwable;

/**
 * JobAttemptSensor — captures one worker execution of a queued job into a
 * `job-attempt` record (daywatch-mcp/docs/agent-protocol.md §2). The attempt is its own execution
 * (`attempt_id`) under the trace inherited from the dispatching request/command.
 * `sync` jobs are skipped (they run inline, captured by their parent execution).
 * Only wired in worker processes.
 */
final class JobAttemptSensor
{
    private UserSensor $users;

    private bool $active = false;

    /** @var array<string, mixed> metadata captured at JobProcessing */
    private array $meta = [];

    public function __construct(private Core $core, ?UserSensor $users = null)
    {
        $this->users = $users ?? new UserSensor($core);
    }

    /** JobProcessing: reset execution state for this attempt and capture metadata. */
    public function processing(object $event): void
    {
        try {
            $connection = (string) ($event->connectionName ?? '');

            if ($connection === 'sync') {
                $this->active = false;

                return;
            }

            $job = $event->job ?? null;

            $this->meta = [
                'name' => $this->jobName($job),
                'connection' => $connection,
                'queue' => $this->queue($job),
                'job_id' => $this->jobId($job),
                'attempt' => $this->attempts($job),
            ];

            $this->active = true;
            $this->core->prepareForJob();
        } catch (Throwable) {
            $this->active = false;
        }
    }

    public function processed(object $event): void
    {
        $this->finish('processed');
    }

    public function released(object $event): void
    {
        $this->finish('released');
    }

    public function failed(object $event): void
    {
        $this->finish('failed');
    }

    private function finish(string $status): void
    {
        try {
            if (! $this->active) {
                return;
            }

            $this->active = false;
            $core = $this->core;

            $record = new JobAttemptRecord(
                envelope: Envelope::for($core, $core->requestStartedAt() ?: $core->clock()->microtime()),
                jobId: (string) ($this->meta['job_id'] ?? ''),
                attemptId: $core->executionId,
                attempt: (int) ($this->meta['attempt'] ?? 1),
                name: (string) ($this->meta['name'] ?? ''),
                connection: (string) ($this->meta['connection'] ?? ''),
                queue: (string) ($this->meta['queue'] ?? 'default'),
                status: $status,
                duration: $this->duration($core),
                counters: $core->counters(),
                peakMemoryUsage: memory_get_peak_usage(true),
                exceptionPreview: $core->exceptionPreview(),
                context: '{}',
            );

            $this->users->capture();
            $core->write($record->toArray());
            $core->finishExecution();
        } catch (Throwable) {
        }
    }

    private function duration(Core $core): int
    {
        $started = $core->requestStartedAt();

        if ($started <= 0.0) {
            return 0;
        }

        return max(0, (int) round(($core->clock()->microtime() - $started) * 1_000_000));
    }

    private function jobName(mixed $job): string
    {
        try {
            if (is_object($job) && method_exists($job, 'resolveName')) {
                return (string) ($job->resolveName() ?? '');
            }

            if (is_object($job) && method_exists($job, 'getName')) {
                return (string) ($job->getName() ?? '');
            }

            if (is_object($job)) {
                return $job::class;
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function queue(mixed $job): string
    {
        try {
            if (is_object($job) && method_exists($job, 'getQueue')) {
                $queue = (string) ($job->getQueue() ?? '');

                if ($queue !== '') {
                    $slash = strrpos($queue, '/');

                    return $slash === false ? $queue : substr($queue, $slash + 1);
                }
            }
        } catch (Throwable) {
        }

        return 'default';
    }

    private function jobId(mixed $job): string
    {
        try {
            if (is_object($job) && method_exists($job, 'payload')) {
                $payload = $job->payload();

                if (is_array($payload)) {
                    return (string) ($payload['daywatch']['job_id'] ?? '');
                }
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function attempts(mixed $job): int
    {
        try {
            if (is_object($job) && method_exists($job, 'attempts')) {
                return (int) $job->attempts();
            }
        } catch (Throwable) {
        }

        return 1;
    }
}

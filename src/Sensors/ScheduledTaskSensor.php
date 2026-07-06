<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\ScheduledTaskRecord;
use Throwable;

/**
 * ScheduledTaskSensor — captures one run of a scheduled task into a
 * `scheduled-task` record (daywatch-mcp/docs/agent-protocol.md §2). Each task run is its own
 * execution under a fresh trace. Only wired in the scheduler process.
 */
final class ScheduledTaskSensor
{
    private UserSensor $users;

    private bool $active = false;

    /** @var array<string, mixed> metadata captured at ScheduledTaskStarting */
    private array $meta = [];

    public function __construct(private Core $core, ?UserSensor $users = null)
    {
        $this->users = $users ?? new UserSensor($core);
    }

    /** ScheduledTaskStarting: begin the task execution and capture its definition. */
    public function starting(object $event): void
    {
        try {
            $task = $event->task ?? null;
            $name = $this->taskName($task);

            $this->meta = [
                'name' => $name,
                'cron' => $this->stringProp($task, 'expression'),
                'timezone' => $this->timezone($task),
                'without_overlapping' => (bool) ($task->withoutOverlapping ?? false),
                'on_one_server' => (bool) ($task->onOneServer ?? false),
                'run_in_background' => (bool) ($task->runInBackground ?? false),
            ];

            $this->active = true;
            $this->core->prepareForScheduledTask($name);
        } catch (Throwable) {
            $this->active = false;
        }
    }

    public function finished(object $event): void
    {
        $this->finish('processed');
    }

    public function skipped(object $event): void
    {
        $this->finish('skipped');
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

            $record = new ScheduledTaskRecord(
                timestamp: $core->requestStartedAt() ?: $core->clock()->microtime(),
                deploy: $core->deploy(),
                server: $core->server(),
                traceId: $core->traceId,
                user: $core->resolveUser(),
                name: (string) ($this->meta['name'] ?? ''),
                cron: (string) ($this->meta['cron'] ?? ''),
                timezone: (string) ($this->meta['timezone'] ?? ''),
                withoutOverlapping: (bool) ($this->meta['without_overlapping'] ?? false),
                onOneServer: (bool) ($this->meta['on_one_server'] ?? false),
                runInBackground: (bool) ($this->meta['run_in_background'] ?? false),
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

    private function taskName(mixed $task): string
    {
        try {
            if (is_object($task)) {
                if (method_exists($task, 'getSummaryForDisplay')) {
                    $summary = (string) $task->getSummaryForDisplay();

                    if ($summary !== '') {
                        return $summary;
                    }
                }

                $command = $this->stringProp($task, 'command');

                if ($command !== '') {
                    return $command;
                }

                $description = $this->stringProp($task, 'description');

                if ($description !== '') {
                    return $description;
                }
            }
        } catch (Throwable) {
        }

        return 'Closure';
    }

    private function stringProp(mixed $task, string $prop): string
    {
        try {
            if (is_object($task) && isset($task->{$prop}) && is_scalar($task->{$prop})) {
                return (string) $task->{$prop};
            }
        } catch (Throwable) {
        }

        return '';
    }

    private function timezone(mixed $task): string
    {
        try {
            if (is_object($task) && isset($task->timezone) && $task->timezone !== null) {
                $tz = $task->timezone;

                if (is_object($tz) && method_exists($tz, 'getName')) {
                    return (string) $tz->getName();
                }

                if (is_scalar($tz)) {
                    return (string) $tz;
                }
            }
        } catch (Throwable) {
        }

        return '';
    }
}

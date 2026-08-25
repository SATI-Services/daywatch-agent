<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\CommandRecord;
use Daywatch\Agent\Records\Envelope;
use Daywatch\Agent\Support\ExecutionStage;
use Throwable;

/**
 * CommandSensor — captures an artisan command execution into a `command` record
 * (agent protocol §2). The command is its own execution root and walks the
 * three command stages (bootstrap → action → terminating). A small set of
 * self-referential / noisy commands (the daemon, status probe, scheduler/worker
 * loops) are never recorded so the collector never observes itself.
 */
final class CommandSensor
{
    /** Commands never recorded — self-observation and long-running loops. */
    private const IGNORED = [
        'daywatch:agent',
        'daywatch:status',
        'schedule:run',
        'schedule:work',
        'queue:work',
        'queue:listen',
        'horizon',
        'horizon:work',
        'horizon:supervisor',
        'package:discover',
    ];

    private StageSensor $stages;

    private UserSensor $users;

    private bool $active = false;

    private string $input = '';

    public function __construct(private Core $core, ?StageSensor $stages = null, ?UserSensor $users = null)
    {
        $this->stages = $stages ?? new StageSensor($core);
        $this->users = $users ?? new UserSensor($core);
    }

    /** CommandStarting: begin the command execution and close the bootstrap stage. */
    public function starting(object $event): void
    {
        try {
            $name = (string) ($event->command ?? '');

            if ($name === '' || in_array($name, self::IGNORED, true)) {
                $this->active = false;

                return;
            }

            $this->active = true;
            $this->input = $this->inputString($event, $name);

            $this->core->prepareForCommand($name);
            $this->stages->advance(ExecutionStage::BOOTSTRAP);
        } catch (Throwable) {
            $this->active = false;
        }
    }

    /** CommandFinished / console lifecycle end: build the record, digest/flush. */
    public function finished(object $event): void
    {
        try {
            if (! $this->active) {
                return;
            }

            $this->active = false;
            $core = $this->core;

            $core->beginStage(ExecutionStage::ACTION);

            $record = new CommandRecord(
                envelope: Envelope::for($core, $core->requestStartedAt() ?: $core->clock()->microtime()),
                class: '',
                name: $core->executionPreview,
                command: $this->input,
                exitCode: (int) ($event->exitCode ?? 0),
                stages: $core->stages(),
                counters: $core->counters(),
                peakMemoryUsage: memory_get_peak_usage(true),
                exceptionPreview: $core->exceptionPreview(),
                context: '{}',
            );

            $core->beginStage(ExecutionStage::TERMINATING);
            $record->stages = $core->stages();

            $this->users->capture();
            $core->write($record->toArray());
            $core->finishExecution();
        } catch (Throwable) {
        }
    }

    private function inputString(object $event, string $name): string
    {
        try {
            $input = $event->input ?? null;

            if (is_object($input) && method_exists($input, '__toString')) {
                $rendered = (string) $input;

                return $rendered !== '' ? $rendered : $name;
            }
        } catch (Throwable) {
        }

        return $name;
    }
}

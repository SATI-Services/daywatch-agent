<?php

declare(strict_types=1);

namespace Daywatch\Agent;

use Daywatch\Agent\Sensors\ExceptionSensor;
use Throwable;

/**
 * The public runtime API behind the {@see Facades\Daywatch} facade
 * (daywatch-mcp/docs/agent-protocol.md §7). A thin, un-crashable delegator over {@see Core}
 * and {@see ExceptionSensor} — every method swallows failures and returns $this.
 */
class Daywatch
{
    /** The Daywatch agent package version. */
    public const VERSION = '1.0.0';

    public function __construct(
        private Core $core,
        private ExceptionSensor $exceptions,
    ) {}

    /** Associate the current execution with a user id (or a resolver callback). */
    public function user(string|int|callable|null $id): static
    {
        try {
            $this->core->user($id);
        } catch (Throwable) {
        }

        return $this;
    }

    /** Force this execution to be sampled (transmitted) at finishExecution(). */
    public function sample(): static
    {
        try {
            $this->core->sample();
        } catch (Throwable) {
        }

        return $this;
    }

    /** Force this execution to be discarded (flushed, not transmitted). */
    public function dontSample(): static
    {
        try {
            $this->core->dontSample();
        } catch (Throwable) {
        }

        return $this;
    }

    /**
     * Record an exception, re-rolling sampling so errors escape sampled-out traces
     * (daywatch-mcp/docs/agent-protocol.md §3, `sampling.exceptions`).
     */
    public function report(Throwable $e): static
    {
        try {
            $this->exceptions->report($e, handled: true);
        } catch (Throwable) {
        }

        return $this;
    }

    /** Suppress an exception from being recorded. */
    public function ignore(Throwable $e): static
    {
        try {
            $this->core->markIgnored($e);
        } catch (Throwable) {
        }

        return $this;
    }

    /** Pause telemetry collection for the rest of this execution. */
    public function pause(): static
    {
        try {
            $this->core->pause();
        } catch (Throwable) {
        }

        return $this;
    }

    /** Resume telemetry collection after a pause(). */
    public function resume(): static
    {
        try {
            $this->core->resume();
        } catch (Throwable) {
        }

        return $this;
    }

    /** Transmit the current buffer to the local daemon immediately. */
    public function digest(): static
    {
        try {
            $this->core->digest();
        } catch (Throwable) {
        }

        return $this;
    }
}

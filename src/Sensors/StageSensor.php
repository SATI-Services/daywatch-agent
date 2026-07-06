<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Support\ExecutionStage;
use Throwable;

/**
 * StageSensor — owns the execution-stage state machine (daywatch-mcp/docs/agent-protocol.md §1).
 * Requests walk `bootstrap → before_middleware → action → render → after_middleware
 * → sending → terminating → end`; commands walk `bootstrap → action → terminating
 * → end`. It closes the running stage (accumulating its µs into {@see Core}) and
 * advances to the next; the per-stage µs land as columns on the request/command
 * record. Stage timing is not itself a record type.
 *
 * CARDINAL RULE: every transition is guarded — a stage-timing failure can never
 * disturb the host request.
 */
final class StageSensor
{
    public function __construct(private Core $core) {}

    /** Close the given stage and advance to its successor in the execution's stage order. */
    public function advance(string $closingStage): void
    {
        try {
            $this->core->beginStage($closingStage);
        } catch (Throwable) {
        }
    }

    /** Close the final timed stage, leaving the execution at `end`. */
    public function stop(): void
    {
        $this->advance(ExecutionStage::TERMINATING);
    }
}

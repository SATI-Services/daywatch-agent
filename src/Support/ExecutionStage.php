<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

/**
 * Execution stages (services/daywatch-mcp/docs/agent-protocol.md §1). Requests walk the seven timed
 * stages plus a terminal `end`; the current stage is stamped onto every
 * sub-record emitted during the request.
 */
final class ExecutionStage
{
    public const BOOTSTRAP = 'bootstrap';

    public const BEFORE_MIDDLEWARE = 'before_middleware';

    public const ACTION = 'action';

    public const RENDER = 'render';

    public const AFTER_MIDDLEWARE = 'after_middleware';

    public const SENDING = 'sending';

    public const TERMINATING = 'terminating';

    public const END = 'end';

    /** The seven timed request stages, in order — the request record's µs columns. */
    public const REQUEST_STAGES = [
        self::BOOTSTRAP,
        self::BEFORE_MIDDLEWARE,
        self::ACTION,
        self::RENDER,
        self::AFTER_MIDDLEWARE,
        self::SENDING,
        self::TERMINATING,
    ];
}

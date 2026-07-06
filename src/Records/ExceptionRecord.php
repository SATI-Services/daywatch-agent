<?php

declare(strict_types=1);

namespace Daywatch\Agent\Records;

use Daywatch\Agent\Support\Group;
use Daywatch\Agent\Support\Truncate;

/**
 * The `exception` record (daywatch/docs/data-model.md §2). `_group` (the Issue key) is
 * xxh128(class|code|file|line). `trace` is a JSON string of source-inlined
 * frames. `code` is a string.
 *
 * Field names are WIRE CONTRACT.
 */
final class ExceptionRecord
{
    public function __construct(
        public float $timestamp,
        public string $deploy,
        public string $server,
        public string $traceId,
        public string $executionId,
        public string $executionSource,
        public string $executionPreview,
        public string $executionStage,
        public string $user,
        public string $class,
        public string $file,
        public int $line,
        public string $message,
        public string $code,
        public string $trace,
        public bool $handled,
        public string $phpVersion,
        public string $laravelVersion,
    ) {}

    public function toArray(): array
    {
        return [
            'v' => 1,
            't' => 'exception',
            'timestamp' => $this->timestamp,
            'deploy' => Truncate::tiny($this->deploy),
            'server' => Truncate::tiny($this->server),
            '_group' => Group::exception($this->class, $this->code, $this->file, $this->line),
            'trace_id' => $this->traceId,
            'execution_id' => $this->executionId,
            'execution_source' => $this->executionSource,
            'execution_preview' => Truncate::tiny($this->executionPreview),
            'execution_stage' => $this->executionStage,
            'user' => Truncate::tiny($this->user),
            'class' => Truncate::tiny($this->class),
            'file' => Truncate::tiny($this->file),
            'line' => $this->line,
            'message' => Truncate::text($this->message),
            'code' => $this->code,
            'trace' => Truncate::medium($this->trace),
            'handled' => $this->handled,
            'php_version' => $this->phpVersion,
            'laravel_version' => $this->laravelVersion,
        ];
    }
}

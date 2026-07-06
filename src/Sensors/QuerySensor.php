<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\QueryRecord;
use Daywatch\Agent\Support\Location;
use Illuminate\Database\Events\QueryExecuted;
use Throwable;

/**
 * QuerySensor — QueryExecuted → `query` record (daywatch/docs/data-model.md §2).
 * SQL is transmitted raw (bindings never substituted); origin file/line comes
 * from a bounded backtrace; `_group` uses the normalized SQL.
 */
final class QuerySensor
{
    private const BACKTRACE_LIMIT = 30;

    public function __construct(private Core $core) {}

    public function handle(QueryExecuted $event): void
    {
        try {
            $core = $this->core;

            // $event->time is milliseconds (float).
            $durationUs = (int) round(((float) $event->time) * 1_000);
            $start = $core->clock()->microtime() - (((float) $event->time) / 1_000);

            [$file, $line] = $this->origin();

            $record = new QueryRecord(
                timestamp: $start,
                deploy: $core->deploy(),
                server: $core->server(),
                traceId: $core->traceId,
                executionId: $core->executionId,
                executionSource: $core->executionSource,
                executionPreview: $core->executionPreview,
                executionStage: $core->executionStage,
                user: $core->resolveUser(),
                sql: (string) $event->sql,
                file: $file,
                line: $line,
                duration: $durationUs,
                connection: (string) $event->connectionName,
                connectionType: $this->connectionType((string) $event->sql),
            );

            $core->recordQuery($record->toArray());
        } catch (Throwable) {
            // telemetry loss is acceptable; a query must never fail to record loudly
        }
    }

    /**
     * First application (non-vendor) frame that issued the query.
     *
     * @return array{0: string, 1: int}
     */
    private function origin(): array
    {
        try {
            $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::BACKTRACE_LIMIT);

            foreach ($frames as $frame) {
                $file = $frame['file'] ?? null;

                if (! is_string($file) || $file === '') {
                    continue;
                }

                $relative = Location::appRelative($file);

                if ($relative !== $file && ! str_starts_with($relative, 'vendor/')) {
                    return [$relative, (int) ($frame['line'] ?? 0)];
                }
            }
        } catch (Throwable) {
        }

        return ['', 0];
    }

    private function connectionType(string $sql): string
    {
        $verb = strtolower(strtok(ltrim($sql), " \t\n\r") ?: '');

        return match ($verb) {
            'select', 'show', 'describe', 'desc', 'explain', 'pragma' => 'read',
            'insert', 'update', 'delete', 'replace', 'merge', 'upsert',
            'truncate', 'create', 'alter', 'drop', 'call' => 'write',
            default => '',
        };
    }
}

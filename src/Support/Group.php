<?php

declare(strict_types=1);

namespace Daywatch\Agent\Support;

/**
 * `_group` fingerprint recipes (services/daywatch-mcp/docs/agent-protocol.md §2). Every record type's
 * grouping hash is an xxh128 hex digest computed client-side so grouping is
 * deterministic and cheap for the ingest/ClickHouse GROUP BYs. These recipes
 * are WIRE CONTRACT — see the `daywatch-payloads` skill before changing one.
 */
final class Group
{
    /** request: xxh128( join('|',methods) . ',' . domain . ',' . path ) */
    public static function request(array $methods, string $domain, string $path): string
    {
        return self::hash(implode('|', $methods).','.$domain.','.$path);
    }

    /** query: xxh128( connection . ',' . normalizedSql ) */
    public static function query(string $connection, string $sql): string
    {
        return self::hash($connection.','.self::normalizeSql($sql));
    }

    /** exception: xxh128( class . '|' . code . '|' . file . '|' . line ) */
    public static function exception(string $class, string $code, string $file, int|string $line): string
    {
        return self::hash($class.'|'.$code.'|'.$file.'|'.$line);
    }

    public static function hash(string $value): string
    {
        return hash('xxh128', $value);
    }

    /**
     * Normalize SQL for grouping only (the transmitted `sql` field stays raw and
     * bindings are NEVER substituted). Collapses `IN (?, ?, ...)` lists and bulk
     * `VALUES (?), (?), ...` tuples so parameter count doesn't fragment groups.
     */
    public static function normalizeSql(string $sql): string
    {
        $sql = (string) preg_replace('/\s+/', ' ', trim($sql));

        // Collapse `in (?, ?, ?)` -> `in (?)`.
        $sql = (string) preg_replace('/\bin\s*\(\s*\?(?:\s*,\s*\?)*\s*\)/i', 'in (?)', $sql);

        // Collapse bulk `values (...), (...), ...` -> a single tuple.
        $sql = (string) preg_replace(
            '/\bvalues\s+(\(\s*\?(?:\s*,\s*\?)*\s*\))(?:\s*,\s*\(\s*\?(?:\s*,\s*\?)*\s*\))+/i',
            'values $1',
            $sql
        );

        return $sql;
    }
}

<?php

declare(strict_types=1);

namespace Daywatch\Agent\Sensors;

use Daywatch\Agent\Core;
use Daywatch\Agent\Records\CacheEventRecord;
use Daywatch\Agent\Records\Envelope;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\ForgettingKey;
use Illuminate\Cache\Events\KeyForgetFailed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWriteFailed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Cache\Events\RetrievingManyKeys;
use Illuminate\Cache\Events\WritingKey;
use Illuminate\Cache\Events\WritingManyKeys;
use Throwable;

/**
 * CacheEventSensor — Illuminate cache events → `cache-event` records
 * (daywatch-mcp/docs/agent-protocol.md §2). Completion events (hit/miss/
 * write/delete and their failures) are paired with their preceding "start"
 * event (Retrieving/Writing/Forgetting) — keyed by store+key — to compute a
 * `duration` in integer microseconds; unpaired completions record duration 0.
 * `ttl` (seconds) is only populated on writes. Every property read is guarded
 * because event shapes vary across Laravel 11/12/13, and every path is
 * try/caught: telemetry loss is fine, throwing into the host app is not.
 */
final class CacheEventSensor
{
    /** Soft cap so an unbalanced start/completion stream can never grow unbounded. */
    private const MAX_PENDING = 1000;

    /** @var array<string, float> store|key → start microtime */
    private array $started = [];

    public function __construct(private Core $core) {}

    /**
     * Entry point the SensorManager wires to every Illuminate cache event.
     */
    public function handle(object $event): void
    {
        try {
            if ($this->isStartEvent($event)) {
                $this->recordStart($event);

                return;
            }

            $type = $this->typeFor($event);

            if ($type === null) {
                return;
            }

            $this->emit($event, $type);
        } catch (Throwable) {
            // telemetry loss is acceptable; the host app must never see this throw
        }
    }

    private function isStartEvent(object $event): bool
    {
        return $event instanceof RetrievingKey
            || $event instanceof RetrievingManyKeys
            || $event instanceof WritingKey
            || $event instanceof WritingManyKeys
            || $event instanceof ForgettingKey;
    }

    private function recordStart(object $event): void
    {
        if (count($this->started) >= self::MAX_PENDING) {
            $this->started = [];
        }

        $store = $this->store($event);
        $now = $this->core->clock()->microtime();

        // Many-key start events carry ->keys; single-key events carry ->key.
        $keys = $this->manyKeys($event);

        if ($keys !== null) {
            foreach ($keys as $key) {
                $this->started[$this->pendingKey($store, (string) $key)] = $now;
            }

            return;
        }

        $this->started[$this->pendingKey($store, $this->key($event))] = $now;
    }

    private function emit(object $event, string $type): void
    {
        $core = $this->core;
        $now = $core->clock()->microtime();

        $store = $this->store($event);
        $key = $this->key($event);

        $pendingKey = $this->pendingKey($store, $key);
        $start = $this->started[$pendingKey] ?? null;
        unset($this->started[$pendingKey]);

        $duration = $start === null ? 0 : (int) round(($now - $start) * 1_000_000);
        $timestamp = $start ?? $now;

        $record = new CacheEventRecord(
            envelope: Envelope::for($core, $timestamp),
            store: $store,
            key: $key,
            type: $type,
            duration: max(0, $duration),
            ttl: $this->ttl($event, $type),
        );

        $core->recordCacheEvent($record->toArray());
    }

    private function typeFor(object $event): ?string
    {
        return match (true) {
            $event instanceof CacheHit => 'hit',
            $event instanceof CacheMissed => 'miss',
            $event instanceof KeyWritten => 'write',
            $event instanceof KeyWriteFailed => 'write-failure',
            $event instanceof KeyForgotten => 'delete',
            $event instanceof KeyForgetFailed => 'delete-failure',
            default => null,
        };
    }

    private function store(object $event): string
    {
        return (string) ($event->storeName ?? '');
    }

    private function key(object $event): string
    {
        return (string) ($event->key ?? '');
    }

    /**
     * @return array<int, mixed>|null the ->keys array for many-key events, else null
     */
    private function manyKeys(object $event): ?array
    {
        $keys = $event->keys ?? null;

        return is_array($keys) ? $keys : null;
    }

    private function ttl(object $event, string $type): int
    {
        if ($type !== 'write' && $type !== 'write-failure') {
            return 0;
        }

        $seconds = $event->seconds ?? 0;

        return is_numeric($seconds) ? (int) $seconds : 0;
    }

    private function pendingKey(string $store, string $key): string
    {
        return $store.'|'.$key;
    }
}

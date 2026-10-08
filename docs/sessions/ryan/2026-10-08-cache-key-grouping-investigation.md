# 2026-10-08 — cache-key grouping investigation (proposal, not yet built)

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## What happened

Ryan wants to explore grouping cache keys in the agent: his project's cache
dashboard lists 10,554 distinct keys, dominated by `redis:sys_setting_<32-hex>`
(one row per hash) plus `lookup_key_<hash>`. Grouping must be configurable per
project, "taking Laravel Pulse as inspiration". This session was
investigation + design only — no code changed.

## Findings

- Current pipeline: `CacheEventSensor` pairs start/completion events by raw
  `store|key`, emits `CacheEventRecord` with the raw `key` and
  `_group = xxh128(store|key)` (`Group::cache()`). The dashboard fragments
  because every hash-suffixed key is its own `_group`.
- Laravel Pulse's actual mechanism (confirmed from source, `1.x`):
  `Recorders\CacheInteractions` calls `$this->group($key)` from
  `Concerns\Groups`, which walks a config map of **regex pattern ⇒
  replacement** (`pulse.recorders.<recorder>.groups`), `preg_replace` with
  `count:`; first match wins, unmatched keys pass through raw. Grouped label
  becomes the recorded key — raw key is NOT retained.
- Key insight for blast radius: if the agent rewrites the `key` value to the
  group label BEFORE building the record, the `_group` recipe
  (`xxh128(store|key)`) and all field names are unchanged — so per the
  `daywatch-payloads` skill this is NOT a wire-contract change: no `v` bump,
  no server/schema/dashboard fan-out. The only docs-first item is adding the
  new option to the shared option table (`agent-protocol.md` §7) in the
  daywatch-docs corpus (same place §7 is already stale for the M4 filters —
  see status/ryan.md open follow-ups).
- Trade-off to put to Ryan: grouped keys lose the raw key permanently
  (Pulse accepts the same loss). Alternative that keeps raw keys = additive
  `key_group` wire field, but that IS a spec-first cross-repo change.
- Existing ignore config (`filtering.ignore_cache_keys` etc.) is `Str::is()`
  globs via `Support\Patterns`; Pulse's is regex map with capture-group
  replacements. Proposal: follow Pulse (regex map) for expressiveness,
  precompiled in a `Support\KeyGrouper` so the hot path stays cheap; invalid
  regex guarded null-safe (never throws into host — cardinal rule).
- Grouping applies at emit only; start/completion pairing must keep using the
  raw key or pairing breaks.
- Env vars can't express a pattern⇒label map, so this would be the first
  config-file-only option (published config), same as Pulse.

## Proposed shape (for Ryan's decision)

```php
// config/daywatch.php — new top-level block
'grouping' => [
    'cache_keys' => [
        '#^sys_setting_.*$#' => 'sys_setting:*',
        '#^lookup_key_.*$#'  => 'lookup_key:*',
    ],
],
```

`AgentServiceProvider` builds `KeyGrouper` from it; `CacheEventSensor::emit()`
maps the key through it before constructing the record. Default empty = zero
overhead, zero behaviour change. Tests: exact-payload assertions (grouped key
+ collapsed `_group`), first-match-wins, unmatched passthrough, bad-regex
swallowed.

Awaiting Ryan's go/no-go and the glob-vs-regex call before implementing.

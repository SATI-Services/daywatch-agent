# 2026-10-08 — Pulse-style `sensors` config + cache-key grouping (shipped)

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## What happened

Morning: investigated grouping cache keys in the agent (Ryan's cache dashboard
listed 10,554 distinct keys, dominated by `redis:sys_setting_<32-hex>` rows),
taking Laravel Pulse as inspiration. Ryan then decided: **no default grouping
— capture everything raw by default — and restructure the config Pulse-style**
(a `recorders`-idiom block where each sensor type can be turned on/off, has
`ignore`, and `groups` where applicable). Built and shipped the same day.

## What changed (daywatch-agent)

- **`config/daywatch.php`** — the `filtering` block is replaced by a
  Pulse-recorders-idiom **`sensors` block keyed by sensor class**. Every
  sensor has `enabled` (new per-sensor env: `DAYWATCH_REQUESTS_ENABLED`,
  `DAYWATCH_QUERIES_ENABLED`, `DAYWATCH_EXCEPTIONS_ENABLED`,
  `DAYWATCH_CACHE_EVENTS_ENABLED`, `DAYWATCH_LOGS_ENABLED`,
  `DAYWATCH_MAIL_ENABLED`, `DAYWATCH_NOTIFICATIONS_ENABLED`,
  `DAYWATCH_OUTGOING_REQUESTS_ENABLED`, `DAYWATCH_QUEUED_JOBS_ENABLED`,
  `DAYWATCH_JOB_ATTEMPTS_ENABLED`, `DAYWATCH_COMMANDS_ENABLED`,
  `DAYWATCH_SCHEDULED_TASKS_ENABLED`). `ignore` lives on Query/Cache/
  QueuedJob/JobAttempt (same env names as before); `groups` (config-file only,
  ships EMPTY) on CacheEventSensor. The two previously RESERVED keys migrated:
  `ignore_outgoing_requests` → OutgoingRequestSensor `enabled` (now actually
  live via the manager gate), `log_level` → LogSensor `level` (still marked
  not-yet-read).
- **`src/Support/KeyGrouper.php`** (new) — Pulse `Concerns\Groups` semantics:
  regex ⇒ replacement, `preg_replace`, first match (count > 0) wins, unmatched
  passes through raw. Patterns are compile-checked ONCE at construction
  (`@preg_match` + try/catch) so a bad regex can never throw or spam warnings
  on the hot path (the suite runs `failOnWarning`; a warning per cache event
  in a host would be just as bad). `KeyGrouper::from()` tolerates non-map
  config values.
- **`CacheEventSensor`** — grouping applied at **emit only**, on the raw key,
  so start/completion pairing (keyed by raw `store|key`) and the ignore list
  (also raw) are undisturbed. The label BECOMES the recorded `key`; raw key is
  not retained (same trade-off Pulse makes). `_group = xxh128(store|label)`
  collapses naturally — **no wire-contract change** (field names and hash
  recipe untouched, no `v` bump, no server fan-out; daywatch-payloads skill).
  The `$ignoreAll` constructor param is gone (see manager gate).
- **`SensorManager`** — new `enabled(string $sensor)` gate; a disabled
  sensor's listeners are never attached. Queue sensors gate independently
  (payload `job_id` injection survives while either is on). This replaces the
  per-sensor `$ignoreAll` constructor flags (also dropped from `QuerySensor`).
- **Legacy back-compat** — a `filtering.*` key present in config can only come
  from a pre-sensors published file (the package no longer ships them), so
  **legacy wins while present**: ignore lists via
  `AgentServiceProvider::sensorIgnore()`, kill-switches (inverted) via the
  manager gate. Old env names keep working throughout — ignore lists read the
  same names, and the three new `enabled` envs default from the old inverted
  kill-switch envs. Config comments tell upgraders to delete `filtering`.
- **Docs** — CLAUDE.md package-shape section (re-mirrored to AGENTS.md);
  Boost guideline gained a consumer-facing "Sensors" section with a
  sys_setting grouping example (this also closes the "Boost guidelines don't
  mention filtering options" follow-up).
- **Tests** — new `tests/Unit/KeyGrouperTest.php` (7 cases incl. invalid
  regex dropped at construction), new `tests/SensorManagerEnabledTest.php`
  (6 cases: default attach, disabled attaches nothing, queue sensors gate
  independently, legacy kill-switches honoured, legacy-wins-over-new,
  legacy-false stays enabled), CacheEventSensorTest +7 grouping/binding
  cases, the old `$ignoreAll` unit tests removed (behaviour moved to the
  gate), ConfigTest asserts the new defaults. The pre-existing container
  tests that set legacy `filtering.*` keys now double as the back-compat
  proof — they pass untouched.

## Validation

- `composer test` — **339 passed** (1073 assertions), 1.99 s (was 321).
- `vendor/bin/pint --test` — PASS (136 files).
- Note: the shell's default `php` is MAMP 8.2 (deps need ≥ 8.3); run with
  `PATH="$HOME/.config/herd-lite/bin:$PATH"` (herd-lite 8.4.1).

## Upgrade-safety proof (same day, Ryan's "must not break ingesting" review)

Ryan's follow-up: the config restructure must be purely a control knob —
ingestion on already-working installs must be unaffected. Verified the merge
semantics and locked them in with tests:

- `ServiceProvider::mergeConfigFrom` is a SHALLOW top-level merge
  (`array_merge(package, app)`) — confirmed in the installed
  `laravel/framework` source. So a 1.0.2-era published config keeps its
  `filtering` block (legacy fallback applies) while the package's `sensors`
  block merges underneath; and a host-defined `sensors` key replaces the
  package's whole block, so EVERY read site carries the shipped defaults as
  code-level fallbacks (`enabled=true`, ignore defaults, `groups=[]`).
- New `tests/Legacy/LegacyConfigTest.php` +
  `tests/Support/LegacyPublishedConfig.php` (a TRAIT overriding
  `defineEnvironment` — Pest can't bind two TestCase classes to overlapping
  dirs, but traits compose): boots the app with the exact `filtering` array a
  1.0.2 published file contributes and asserts end-to-end that cache events
  and queries still record, framework noise is still ignored, keys record
  raw with no grouping, and a host-set legacy kill-switch still gates.
- Partial-block tests: a host `sensors` block defining only cache `groups`
  keeps every sensor attaching (listener counts) and keeps the shipped
  `*illuminate:*` ignore default while grouping applies.
- `composer test` — **344 passed** (1082 assertions); pint PASS (138 files).
- Untouched by the whole change: `SocketClient`/framing, `RecordsBuffer`,
  daemon batching/retry, `Payload` — the digest path has zero diffs.

## Decisions worth remembering

- Grouping rewrites `key` pre-record ⇒ deterministic `_group` collapse with
  zero wire-contract surface. The alternative (keep raw key + additive
  `key_group` field) was rejected for v1 — that's a spec-first cross-repo
  change if ever wanted.
- `sampling` stays execution-wide (protocol §3), deliberately NOT folded into
  per-sensor `sample_rate` like Pulse — a Daywatch trace is complete or
  absent, never partial.

## Open follow-ups

- Docs corpus `agent-protocol.md` §7 option table needs the `sensors` block
  (and still lacks the M4 filters — same stale-§7 item in status/ryan.md).
- `groups` could later generalise to other high-cardinality values
  (outgoing-request URLs, Pulse groups those too) — mechanism is generic,
  wiring deferred.

## Release — 1.1.0

Ryan asked for the version bump same day. `Daywatch::VERSION` `1.0.2` →
`1.1.0` (minor: additive `sensors` config + grouping, legacy `filtering.*`
back-compat kept — no breaking change for existing installs). Suite re-run on
the bump: 344 passed. Tagged `1.1.0` (lightweight, matching the
1.0.0/1.0.1/1.0.2 pattern) and pushed with main.

## Commits

- (this session) `feat(sensors): Pulse-style per-sensor config + cache-key grouping`
  (code + config + tests + docs), pushed to main.
- (this session) `test(config): prove ingestion survives 1.0.x published configs`
  (legacy-boot suite + partial-`sensors`-block tests), pushed to main.
- (this session) `chore(release): bump package version to 1.1.0` + tag `1.1.0`
  (lightweight, matching the 1.0.x pattern), pushed to main.
- (earlier) `docs(sessions): log cache-key grouping investigation (proposal pending)`.

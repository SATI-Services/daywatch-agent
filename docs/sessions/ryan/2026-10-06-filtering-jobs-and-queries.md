# 2026-10-06 — filtering: ignore_query_patterns + ignore_job_names (M4 pull-forward)

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## What happened

Ryan gave the go-ahead for the M4 query-filtering work queued in
`status/ryan.md` (surfaced by the 2026-10-05 queue-poll question), with two
extensions: a jobs analogue of `ignore_cache_keys`, and shipped default query
needles covering the framework's internal tables
(`str_contains($sql, … 'jobs', 'cache_locks', 'cache', 'sessions', 'batches')`).

## What changed (daywatch-agent)

- **`src/Support/Patterns.php` (new)** — the needle/glob precompile that lived
  inside `CacheEventSensor`, extracted so every filter shares it. Plain
  `*substring*` globs become `str_contains` needles (hot path: every query,
  cache event and dispatch); real glob structure falls back to `Str::is()`.
  `Patterns::from()` normalises array-or-CSV options (the provider's old
  `patterns()` helper, removed).
- **`QuerySensor`** — now reads the previously RESERVED `filtering.ignore_queries`
  (drop the whole stream) and the new `filtering.ignore_query_patterns`
  (default `*jobs*,*cache*,*sessions*,*batches*`, env
  `DAYWATCH_IGNORE_QUERY_PATTERNS`). Filter runs before anything is buffered;
  ignored queries don't bump the `queries` counter. `*cache*` subsumes
  `cache_locks`; plain needles match any grammar quoting (`"jobs"` / `` `jobs` ``
  / `[jobs]`), per the 2026-10-05 finding.
- **`filtering.ignore_job_names`** (new, default empty, env
  `DAYWATCH_IGNORE_JOB_NAMES`) — matched against the job display name / class
  in **both** `QueuedJobSensor` (no queued-job record; the stashed
  JobQueueing timestamp is discarded so it can't leak into the next job's
  duration) and `JobAttemptSensor` (no execution is started — the attempt and
  everything it does is invisible).
- `CacheEventSensor` refactored onto `Patterns` (no behaviour change).
- Config header comment updated: LIVE list now covers the five wired options;
  still reserved: `ignore_outgoing_requests`, `log_level`.

## Gotcha worth remembering

Sensor singletons are resolved once at boot by `SensorManager::register()`,
so `config()->set(...)` in a test (or at runtime) has no effect on an
already-resolved sensor. Container-wiring tests must `forgetInstance()` and
re-resolve — the pre-existing cache container test only passed because it
re-set the default value.

## Follow-ups (not done here)

- `agent-protocol.md` §7 in the docs corpus (daywatch-mcp repo) still lists
  the old `filtering.*` line — it already lacked `ignore_cache_keys`; a
  corpus refresh is a separate-repo change.
- Boost guidelines (`resources/boost/guidelines/core.blade.php`) don't
  mention the filtering options yet.

## Validation

- `composer test` — 321 passed (1034 assertions); new: PatternsTest (4),
  QuerySensor filter cases (5), QueuedJobSensor (4), JobAttemptSensor (1),
  ConfigTest defaults.
- `vendor/bin/pint --test` — PASS (133 files).

## Commits

- (this session) feat + docs(sessions), pushed to main

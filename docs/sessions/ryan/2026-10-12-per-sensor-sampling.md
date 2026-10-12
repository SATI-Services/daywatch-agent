# 2026-10-12 — sampling audit + per-sensor record sampling

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## Context (from Ryan)

Data volume is getting intensive: some areas don't need as much collection and
some can be sampled. Two asks: (1) confirm the `daywatch.sampling` config
actually works, (2) can `sensors.{the_sensor}` sample individual things like
queries and cache events.

## Part 1 — `daywatch.sampling` audit: works correctly, now regression-tested

Traced the whole path; no bug found.

- `config/daywatch.php` defines `sampling.requests / .commands /
  .scheduled_tasks / .exceptions` (env-backed, default 1.0) →
  `AgentServiceProvider` reads all four with `(float)` casts into `Core`
  (positional order verified against the constructor).
- `Core::prepareForRequest/Command/ScheduledTask` each decide head sampling
  once per execution (`decideSampling`, fast paths at ≥1.0 / ≤0.0);
  `finishExecution()` digests when sampled, flushes otherwise.
- Exceptions re-roll at `sampling.exceptions` (`Core::recordException`) so
  errors escape sampled-out traces; `Daywatch::sample()/dontSample()` override
  either way; `reset()` forces sampling off between worker/Octane executions.
- Job executions intentionally make NO local decision: they inherit the
  dispatching execution's choice via hidden Context keys (protocol §3 — a
  trace is complete or absent). There is no `sampling.jobs` key by design.

Gap found: every sampling test hand-built `Core` — nothing proved the
*container wiring* (config → provider → Core). Added
`tests/SamplingWiringTest.php`: overrides config, forgets the singleton (boot
builds Core once via SensorManager, so in-test overrides land too late for the
boot-built instance) and re-resolves through the real provider factory —
requests/commands/scheduled-tasks at 0.0 sample nothing, exception re-roll
wires through, defaults sample everything.

## Part 2 — new: per-sensor record sampling (`sensors.{class}.sample`)

New option on the seven high-volume child-record sensors, each with an env var
mirroring its `_ENABLED` name (all default 1.0 = keep everything):

| Sensor | env var |
| --- | --- |
| QuerySensor | `DAYWATCH_QUERIES_SAMPLE_RATE` |
| CacheEventSensor | `DAYWATCH_CACHE_EVENTS_SAMPLE_RATE` |
| LogSensor | `DAYWATCH_LOGS_SAMPLE_RATE` |
| MailSensor | `DAYWATCH_MAIL_SAMPLE_RATE` |
| NotificationSensor | `DAYWATCH_NOTIFICATIONS_SAMPLE_RATE` |
| OutgoingRequestSensor | `DAYWATCH_OUTGOING_REQUESTS_SAMPLE_RATE` |
| QueuedJobSensor | `DAYWATCH_QUEUED_JOBS_SAMPLE_RATE` |

Semantics (two deliberately separate layers):

- Head sampling (`sampling.*`) stays per-execution: a trace is complete or
  absent, never partial.
- `sample` thins child records WITHIN an execution: each record rolls
  independently in `Core::keepsRecord()` (`mt_rand` — per-record hot path, not
  security-sensitive). The execution's **counters always increment**, so the
  request record still reports "342 queries" while only a `sample`-fraction of
  the query rows is buffered/sent.
- Execution-root records (request, command, scheduled task, job attempt) and
  exceptions take NO rate: a dropped root would orphan its trace, and
  exceptions are already governed by `sampling.exceptions`.

Implementation is Core-only — sensors are untouched: the seven
`Core::record{Query,CacheEvent,OutgoingRequest,Log,Mail,Notification,
QueuedJob}` methods increment their counter, then buffer only when the roll
keeps the record. Provider maps `daywatch.sensors.{class}.sample` into Core's
new `recordSampleRates` constructor map. No wire-contract change (record
shapes, framing, counters all unchanged — purely a producer-side collection
decision, same class of change as `ignore`), so no docs-first payload-skill
round-trip needed; the §7 option-table refresh is noted as a follow-up.

Not wire contract, but worth stating: sampled-out records still pay the
sensor's record-BUILDING cost (e.g. the query sensor's bounded backtrace) —
the savings are buffer/socket/daemon/ingest volume, which was the ask.

## Tests

355 passing (344 baseline + 11 new), pint clean:

- `tests/CoreTest.php` — per-type rates: default keeps all; 0.0 drops the
  rows but counters still count every event; types roll independently; all
  seven types gated; seeded mid-rate (0.25 over 1000 records) keeps ~25%.
- `tests/SamplingWiringTest.php` (new) — container wiring for both layers.
- `tests/ConfigTest.php` — `sample` 1.0 default asserted for all seven sensors.

## Docs updated

- `config/daywatch.php` — `sample` documented in the `sensors` header; the old
  "head sampling is deliberately NOT per-sensor" note rewritten as the
  two-layer explanation.
- `CLAUDE.md` (+ `AGENTS.md` mirror) — package-shape line and a non-negotiable
  behaviors bullet for the second sampling layer.
- `resources/boost/guidelines/core.blade.php` — consumer-facing `sample`
  example.

## Files touched

- `src/Core.php`, `src/AgentServiceProvider.php`, `config/daywatch.php`
- `tests/CoreTest.php`, `tests/ConfigTest.php`, `tests/Support/helpers.php`,
  `tests/SamplingWiringTest.php` (new)
- `CLAUDE.md`, `AGENTS.md`, `resources/boost/guidelines/core.blade.php`
- this log + `docs/sessions/status/ryan.md` + `docs/sessions/SHARED.md`

## Follow-ups

- Docs corpus `agent-protocol.md` §7 option table: add the seven `sample`
  options (already-stale entry in status/ryan.md extended).
- Release candidate for `1.2.0` (feature addition) — VERSION bump + tag left
  for a release session, per convention.

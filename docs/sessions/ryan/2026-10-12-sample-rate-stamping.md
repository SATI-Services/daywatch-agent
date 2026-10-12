# 2026-10-12 — Emit-side: stamp `sample_rate` (effective inclusion probability) on every record

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## Why

Operator is building per-sensor/head sampling (1.2.0 shipped today) and asked
how the dashboard will know data is sampled (design Q&A logged in the central
app repo: `docs/sessions/ryan/2026-10-12-sampled-data-dashboard-design.md`).
The blocker: the sampling decision never reached the server — the envelope had
no rate field, so the dashboard could neither extrapolate counts nor show an
indicator, and a 10% sample looked exactly like a 90% traffic drop.

Spec landed first (docs-first rule): daywatch-mcp `docs/agent-protocol.md`
commits `7512dc4` + `a2a69ef` — `sample_rate` float (0,1], **conditional,
present only when < 1** (absent ≡ 1.0), trailing key; `v` unchanged; canonical
sample batch deliberately untouched (ordered parity fixtures never carry a
conditional trailing key — `FieldParityTest` unaffected, proven green).

## What changed (this repo)

- `src/Core.php` — new `effectiveSampleRate` (the rate of the roll that WON):
  head rate at `prepareForRequest/Command/ScheduledTask`; `sampling.exceptions`
  when `recordException()`'s re-roll rescues a sampled-out execution (stamping
  the head rate there would over-weight rescued rows ~1/rate); 1.0 for
  `Daywatch::sample()` (a force is not a probability draw); the upstream rate
  adopted from propagation. New public `sampleRate()` accessor. `digest()`
  stamps every record at DIGEST time (decision is final there — a record built
  before a rescue still gets the rescue rate), multiplying by the per-sensor
  roll for the seven rate-gated child types (`RATE_GATED_TYPES`); omitted when
  the product is 1.0. `reset()` restores 1.0 at the worker boundary.
- `src/Support/ContextPropagation.php` — new `daywatch_sample_rate` hidden key
  so a queue hop weights identically to its upstream (a sampled request's job
  attempts must not extrapolate against the job rate). Read validates (0,1],
  anything else → null → 1.0 default. Older upstream agents simply never write
  it.
- `tests/CoreTest.php` — 6 new tests: unstamped at 1.0; propagated rate stamps
  roots AND children; head×sensor product (0.5×0.5=0.25 on kept query rows);
  head-rate→exceptions-rate transition on rescue; force `sample()` stamps
  nothing; propagation read validation. Deterministic levers only (boundary
  rates, propagation, mt_srand) — no mid-rate head rolls.

## Tested

- `vendor/bin/pest` — **361 passed** (355 at 1.2.0 + 6 new), 1129 assertions.
- `vendor/bin/pint --dirty` — clean (one import-ordering fix applied, re-green).
- Suite needs no services; ran on host PHP 8.4.1 (herd-lite).

## Notes / follow-ups

- Mid-execution auto-digest stamps with the then-current rate; a later rescue
  can't un-stamp already-transmitted rows (pre-existing partial-trace class,
  unchanged by this change).
- Server-side fan-out (ingest transformer, ClickHouse columns, dashboard
  extrapolation + badge) lands in the central app repo; relay pass-through in
  daywatch-ingest — both tracked in the central repo's session log.
- §7 option-table staleness (open follow-up in status/ryan.md) is now partly
  addressed: the `sample_rate` wire field + the seven per-sensor env vars are
  in the corpus. The wider `sensors` block refresh remains open.

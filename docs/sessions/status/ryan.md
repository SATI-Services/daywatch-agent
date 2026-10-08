# ryan — status

Live work-in-progress and open follow-ups. **Only ryan writes this file** — that's what makes
it collision-free against the others'. Cross-cutting items go in [../SHARED.md](../SHARED.md);
historical detail lives in the dated logs under `../ryan/`.

> Seeded 2026-09-06 by the AID adoption backfill from git history (`--since 2026-06-08`). Entries below
> are reconstructed from commits, not contemporary notes.

_Last touched: 2026-10-08 — Pulse-style `sensors` config + cache-key grouping shipped (unreleased)._

## In flight

- (nothing)

## Next up

1. (triage anything the board surfaces)

## Open follow-ups

- Docs corpus `agent-protocol.md` §7 option table is stale: it lacks the new
  `sensors` block (per-sensor `enabled`/`ignore`/`groups`) AND the M4 filters
  it replaced (`ignore_cache_keys`, `ignore_query_patterns`,
  `ignore_job_names`) — refresh in the daywatch-mcp repo when next touching
  the corpus.
- Consider `groups` for other high-cardinality values (outgoing-request URLs)
  — `Support\KeyGrouper` is generic; wiring deferred.
- Next release: the sensors-config change restructures `config/daywatch.php`
  (legacy `filtering.*` keys still honoured) — worth an UPGRADE note when
  Ryan cuts it.

## Shipped

_Seeded from git history, newest first:_

- 2026-10-08 **Pulse-style `sensors` config + cache-key grouping** — per-sensor
  `enabled`/`ignore`/`groups` keyed by class (legacy `filtering.*` fallback),
  `Support\KeyGrouper` (Pulse `Concerns\Groups` semantics), SensorManager
  enabled gate, 339 tests green →
  [log](../ryan/2026-10-08-sensors-config-and-cache-key-grouping.md)

- 2026-10-06 **`1.0.2` tagged** — M4 filtering release (`ignore_queries` wired,
  `ignore_query_patterns`, `ignore_job_names`); `Daywatch::VERSION` bump only,
  321 tests green → [log](../ryan/2026-10-06-release-1.0.2.md)
- 2026-10-06 M4 filtering: `ignore_queries` wired + new `ignore_query_patterns`
  (default drops framework-internal tables) + new `ignore_job_names`
  (queued-job AND job-attempt); shared `Support\Patterns` matcher; 321 tests
  green → [log](../ryan/2026-10-06-filtering-jobs-and-queries.md)
- 2026-08-25 wip — `9b42e77`
- 2026-08-25 wip — `7b6b60d`
- 2026-08-25 wip — `6de7637`
- 2026-08-24 wip — `4f7db05`
- 2026-07-07 agent mvp — `9a3caa4`
- 2026-07-06 wip — `df29adb`

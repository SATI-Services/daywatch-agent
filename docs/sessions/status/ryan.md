# ryan — status

Live work-in-progress and open follow-ups. **Only ryan writes this file** — that's what makes
it collision-free against the others'. Cross-cutting items go in [../SHARED.md](../SHARED.md);
historical detail lives in the dated logs under `../ryan/`.

> Seeded 2026-09-06 by the AID adoption backfill from git history (`--since 2026-06-08`). Entries below
> are reconstructed from commits, not contemporary notes.

_Last touched: 2026-10-09 — audited: agent issues no queries of its own (observes only; `auth()->user()` guard-read nuance)._

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
- Host apps on 1.0.x published configs: no action needed on upgrade to 1.1.0
  (legacy `filtering.*` keys still honoured), but the published file's
  `filtering` block can be deleted when convenient.

## Shipped

_Seeded from git history, newest first:_

- 2026-10-09 audit (read-only): confirmed the agent issues **no queries of
  its own** — sensors are pure event listeners; only nuance is
  `auth()->user()` can trigger the framework guard's lazy `retrieveById()`
  SELECT on public routes for logged-in visitors →
  [log](../ryan/2026-10-09-agent-user-query-audit.md)
- 2026-10-08 ops: `daywatch-docs` MCP server (`:8091`) was down — diagnosed
  (nothing listening; error body was a stray occupant's Laravel 500) and
  restarted from the daywatch-mcp checkout; 3 tools verified →
  [log](../ryan/2026-10-08-mcp-docs-server-restart.md)
- 2026-10-08 **`1.1.0` tagged** — Pulse-style `sensors` config + cache-key
  grouping release; 344 tests green →
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

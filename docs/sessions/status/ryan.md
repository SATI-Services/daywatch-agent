# ryan — status

Live work-in-progress and open follow-ups. **Only ryan writes this file** — that's what makes
it collision-free against the others'. Cross-cutting items go in [../SHARED.md](../SHARED.md);
historical detail lives in the dated logs under `../ryan/`.

> Seeded 2026-09-06 by the AID adoption backfill from git history (`--since 2026-06-08`). Entries below
> are reconstructed from commits, not contemporary notes.

_Last touched: 2026-10-08 — cache-key grouping investigation (proposal pending Ryan's decision)._

## In flight

- Cache-key grouping (Pulse-style `groups` regex map ⇒ key label, rewriting
  `key` pre-record so `_group` collapses with no wire-contract change) —
  investigated 2026-10-08, awaiting go/no-go + glob-vs-regex call →
  [log](../ryan/2026-10-08-cache-key-grouping-investigation.md)

## Next up

1. (triage anything the board surfaces)

## Open follow-ups

- Docs corpus `agent-protocol.md` §7 filtering line is stale (lacks
  `ignore_cache_keys`, `ignore_query_patterns`, `ignore_job_names`) — refresh
  in the daywatch-mcp repo when next touching the corpus.
- Boost guidelines don't mention the filtering options yet.

## Shipped

_Seeded from git history, newest first:_

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

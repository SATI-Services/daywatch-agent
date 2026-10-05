# ryan — status

Live work-in-progress and open follow-ups. **Only ryan writes this file** — that's what makes
it collision-free against the others'. Cross-cutting items go in [../SHARED.md](../SHARED.md);
historical detail lives in the dated logs under `../ryan/`.

> Seeded 2026-09-06 by the AID adoption backfill from git history (`--since 2026-06-08`). Entries below
> are reconstructed from commits, not contemporary notes.

_Last touched: 2026-10-05 — query-filtering question answered (no code change)._

## In flight

- (nothing in code — 2026-10-05 was a Q&A on filtering the DB-queue poll query; see `../ryan/2026-10-05-query-filtering.md`)

## Next up

1. Wire the reserved `filtering.ignore_queries` + add `ignore_query_patterns` (needle/glob, mirroring `CacheEventSensor`) in `QuerySensor` — M4 filtering work, surfaced by the 2026-10-05 question. Awaiting Ryan's go-ahead.
2. (then triage anything the board surfaces)

## Open follow-ups

- (none)

## Shipped

_Seeded from git history, newest first:_

- 2026-08-25 wip — `9b42e77`
- 2026-08-25 wip — `7b6b60d`
- 2026-08-25 wip — `6de7637`
- 2026-08-24 wip — `4f7db05`
- 2026-07-07 agent mvp — `9a3caa4`
- 2026-07-06 wip — `df29adb`
- 2026-07-06 ingest — `62e60ae`
- 2026-07-06 streamline agent — `4f76258`

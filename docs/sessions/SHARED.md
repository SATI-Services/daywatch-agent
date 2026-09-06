# Shared state — read first (cross-cutting only)

Thin, **group-owned** snapshot of things that affect *everyone*: shared blockers, prod-wide infra/transients, credential-rotation reminders, and a short recently-shipped ticker. Per-author WIP lives in [`status/<author>.md`](status/) — **do not put one author's WIP here.**

**Rules:** keep ≤ ~50 lines; only add items that concern more than one author; tag with `[from YYYY-MM-DD]`; edits should mostly be *appends* so they merge cleanly.

---

## Per-author status (live WIP + open follow-ups)

- **Ryan** → [status/ryan.md](status/ryan.md)


Open PRs (Track B in flight) are surfaced live by the session-start hook via `gh pr list`. Inter-author notes live in [`notes/`](notes/).

## Current priorities

1. Shake out the scheduled `tests.yml` CI (first cron + push runs green after AID adoption) [from 2026-09-06].
2. Post-`1.0.0` hardening of the agent package — next milestone not yet named; watch the board after first sync [from 2026-09-06].

## Shared blockers

- **`tests.yml` Laravel 11 legs red (pre-existing, never green since 2026-07-06)** [from 2026-09-06]: `composer update` on the `^9.0` testbench leg fails because every `laravel/framework` 11.x release is now blocked by Packagist security advisories under composer's audit policy (`PKSA-m5cs-t1y6-qpcs` et al.). PHP 8.3/8.4 × Laravel 12/13 legs are green. Owner decision needed (host-compat contract): composer `policy.advisories` config, a testbench pin that resolves to unblocked 11.x, or dropping the Laravel 11 leg.

## Known transients (not bugs — do not chase)

- none recorded yet

## Open security / rotation reminders

- none in this repo — `DAYWATCH_TOKEN` / `DAYWATCH_BASE_URL` live in host apps' `.env`, never here [from 2026-09-06].

## Recently shipped (5 newest — full history in dated logs)

- 2026-08-25 **`1.0.0` tagged** — M2 complete: 14 sensors, ReactPHP daemon + STATS surface, 300 tests green → [log](ryan/2026-08-25-backfill.md)
- 2026-08-24 wip → [log](ryan/2026-08-24-backfill.md)
- 2026-07-07 agent MVP → [log](ryan/2026-07-07-backfill.md)
- 2026-07-06 ingest + streamline agent → [log](ryan/2026-07-06-backfill.md)
- 2026-07-03 first commit → [log](ryan/2026-07-03-backfill.md)

---

## Adoption notes [2026-09-06]

- AID harness adopted 2026-09-06 (AID v1.5). `.claude/skills/parity-audit` deleted (PingPath-specific, N/A here); `.claude/skills/live-verify` deleted (package has no rendered surface and no checked-in headless driver — the "hostile host" suite + `daywatch:status` are the real-surface check).
- Pre-existing `CLAUDE.md`/`AGENTS.md` (the engineering guide) were kept intact; an "Operating manual (AID harness)" pointer section was appended, `CLAUDE.md` → `AGENTS.md` mirror preserved.

---

## Backfill provenance [2026-09-06]

Session docs below `docs/sessions/` dated before 2026-09-06 were retro-seeded from git history
(`--since 2026-06-08`, first-parent on `main`) during AID adoption. Bot/exporter authors skipped
({}). Earlier history predates the window; releases are the pre-window record.

Release timeline (1 tag dates total, 10 newest):
- 2026-08-25: `1.0.0`

# kimi → ryan — 2026-09-06 — CI matrix fixes (board #157)

**Agent:** kimi · **For:** ryan · Board task #157 (owner-blessed 2026-09-06)

## daywatch-agent (this repo) — FIXED

`tests.yml` had never been green: both Laravel 11 matrix legs failed at
`composer update` because **every** `laravel/framework` 11.x release is blocked by
Packagist security advisories under composer's audit policy
(`PKSA-m5cs-t1y6-qpcs`, `PKSA-3r5d-mb8f-1qw9`, `PKSA-mdq4-51ck-6kdq`, …), so
`orchestra/testbench ^9.0` is unresolvable. Evidence: run `34062628393`.

**Change (matrix only):** dropped the Laravel 11 legs from
`.github/workflows/tests.yml` (`laravel: ['11','12','13']` → `['12','13']`, plus the
`testbench: '^9.0'` include entry). PHP 8.3/8.4 × Laravel 12/13 legs unchanged.
Fresh-installing an advisory-blocked framework line is untestable in principle;
consumers pinned on L11 keep working from their own lockfiles.

- Fix commit: `33ab20f` — "ci: drop advisory-blocked laravel 11 legs from tests matrix"
- Validation: dispatch run `34064976005` (`gh workflow run tests.yml --ref main`) — **green**, all 4 legs; push-triggered run `34064969183` also green.

**Divergence you should know about:** `composer.json` still advertises
`illuminate/support: ^11.0|^12.0|^13.0` — I did not rewrite the declared support
(not mine to narrow). The support claim and CI testability now diverge: L11 is
supported-but-untestable upstream. If you want them re-aligned, that's either a
composer `policy.advisories` config or narrowing the constraint — your call.
Related doc drift (left untouched, "edit matrix only" scope): `AGENTS.md`/`CLAUDE.md`
still say the CI matrix covers Laravel 11/12/13 and cite testbench `^9.2` (workflow
said `^9.0`).

## daywatch (sibling) — NOT fixed, halted per stop condition

Recorded there as the same note filename. Short version: the planned "drop PHP 8.3
from the matrix" fix does **not** apply — `composer.json` declares `"php": "^8.3"`,
so the matrix already matches the declared floor. The real bug is the lockfile:
`composer.lock` (f23f7fb) pins symfony v8.1.x which requires PHP ≥8.4.1. Fix is a
lockfile re-resolve on PHP ≥8.4 (or a composer.json php bump) — owner decision,
not a CI edit. No code changed there.

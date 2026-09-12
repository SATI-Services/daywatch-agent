---
title: Laravel 11 legs dropped from CI matrix; advisory-blocked upstream
tags: [ci, laravel, unresolved]
status: verified 2026-09-12 auto
source: [docs/sessions/notes/2026-09-06-kimi-to-ryan-ci-fixes.md]
as_of: a5e5084a1 2026-09-12
volatile: true
---
Laravel 11 matrix legs removed from `.github/workflows/tests.yml` (Laravel 12/13 remain). Every Laravel 11.x release blocked by Packagist security advisories (`PKSA-m5cs-t1y6-qpcs`, `PKSA-3r5d-mb8f-1qw9`, `PKSA-mdq4-51ck-6kdq`), making `orchestra/testbench ^9.0` unresolvable at `composer update`.

**Why:** Fresh-installing an advisory-blocked framework line is untestable in principle upstream. Consumers pinned on Laravel 11 keep working from their own lockfiles. CI now tests Laravel 12/13 only (PHP 8.3/8.4 × L12/L13, 4 legs total, green as of run 34064976005).

**Divergence:** `composer.json` still declares `illuminate/support: ^11.0|^12.0|^13.0` — support claim and CI testability now diverge. Narrowing the constraint or configuring `composer policy.advisories` would re-align them (owner decision).

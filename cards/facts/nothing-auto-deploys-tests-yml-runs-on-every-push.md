---
title: Nothing auto-deploys — there is no deploy; tests.yml runs on every push
tags: [ci, deploy, git]
status: verified 2026-11-07 auto
source: [docs/WORKFLOW.md]
as_of: 34b58366e 2026-11-06
---
There is no deploy step — nothing auto-deploys. `main` is validated by the `tests.yml` CI run (Pest matrix, PHP 8.3/8.4 × Laravel 11/12/13, a few minutes), which runs on every push.

**Why:** `main` is validated by CI, not deployed — the "merge triggers deploy" phrasing in the manual's Track B section does not hold for this repo.

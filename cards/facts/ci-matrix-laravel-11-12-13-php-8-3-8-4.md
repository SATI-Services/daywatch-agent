---
title: CI matrix runs Pest on Laravel 11/12/13 × PHP 8.3/8.4
tags: [ci, testing, constraints]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: da814d4f7 2026-10-31
---
`tests.yml` runs the Pest + Testbench suite on every push (docs-only included) across Laravel 11/12/13 × PHP 8.3/8.4. The same matrix is scheduled (cron `17 6 * * *`) as the daily E2E.

**Why:** The scheduled run is the project's daily E2E regression net.

---
title: require-dev pinned tilde-lock; CI swaps testbench constraint per Laravel line before composer update
tags: [dependencies, ci, composer]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: 41d44b355 2026-10-31
---
`require-dev` is pinned tilde-lock for reproducible dev installs (testbench `~11.1.0`, pest `~4.7.4`, pest-plugin-laravel `~4.1.0`, pint `~1.29.3`); the CI matrix swaps the testbench constraint per Laravel line (`^9.2`/`^10`/`^11`) before `composer update`, so the pin only governs local dev.

**Why:** Pinned dev deps ensure every dev machine has the same tooling. CI varies testbench to match the Laravel version under test.

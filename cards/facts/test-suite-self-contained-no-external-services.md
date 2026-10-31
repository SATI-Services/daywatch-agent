---
title: The Pest suite is self-contained — no daemon, ingest, MySQL or Redis needed
tags: [testing, dev, dependencies]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: da814d4f7 2026-10-31
---
`composer install && composer test` executes the whole Pest + Testbench suite against in-memory SQLite with zero external dependencies — no ClickHouse, Redis, MySQL, daemon, or running ingest. The "hostile host" suite proves the package works with no server at all.

**Why:** This is a composer package, not an app — no `.env`, no database, no services to run.

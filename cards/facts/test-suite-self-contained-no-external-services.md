---
title: Test suite is self-contained; runs on in-memory SQLite, needs no daemon/ingest/MySQL/Redis
tags: [testing, suite, ci]
status: verified 2026-09-09 auto
source: [README.md, CLAUDE.md]
as_of: dbb7df637 2026-09-09
---
`composer test` runs the full Pest suite self-contained against an in-memory SQLite database. The suite needs no external services: no daemon, ingest, MySQL, or Redis. The CI matrix runs the suite across Laravel 11, 12, and 13 lines.

**Why:** Self-contained tests are fast and reliable, removing external flakiness and making local development and CI dependable.

---
title: Test suite self-contained; runs on in-memory SQLite, needs no daemon/ingest/MySQL/Redis
tags: [testing, dependencies, dev]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: 41d44b355 2026-10-31
---
Pest + Testbench suite is self-contained: `composer install && composer test` needs no companion service — no ClickHouse, Redis, MySQL, or running server. Sensor tests assert exact record payloads (field names are wire contract). Socket client tested against a stub TCP server; daemon batching/retry tested with ReactPHP's test utilities.

**Why:** A dev can run the full suite on a laptop. No external services mean no flaky setup and no network coupling.

---
title: Hostile-host suite proves nothing throws when daemon down, token wrong, or payloads oversized
tags: [testing, suite, hostile]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: 41d44b355 2026-10-31
---
A "hostile host" suite explicitly proves the package works (silently) with no server at all: no ClickHouse, Redis, MySQL, or running server. The suite proves nothing throws when the daemon is down, the token is wrong, or payloads are oversized.

**Why:** The package must be uncrashable even when all downstream systems fail. The hostile suite is the proof.

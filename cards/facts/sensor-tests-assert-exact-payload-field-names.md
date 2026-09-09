---
title: Sensor tests assert exact payload field names; those names ARE the wire contract
tags: [sensors, testing, wire-contract]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md]
as_of: 16a6c506a 2026-09-06
---
Sensor tests assert exact record payload field names. Those names ARE the wire contract; renaming a field is a cross-repo change (docs-first via `daywatch-payloads` skill), not a package-local refactor.

**Why:** Field names travel to the central ingest and must remain stable across package versions and repo deployments.

---
title: Sensor tests assert exact payload field names; those names ARE the wire contract
tags: [testing, wire-contract, sensors]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: 41d44b355 2026-10-31
---
Sensor tests assert exact record payloads. The field names ARE the wire contract. Renaming a field is a cross-repo change (docs-first via the `daywatch-payloads` skill), not a package-local refactor.

**Why:** Field names are part of the wire contract. Every host app that digests records depends on those names being stable.

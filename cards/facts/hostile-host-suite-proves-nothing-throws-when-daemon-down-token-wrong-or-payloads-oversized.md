---
title: Hostile-host suite proves nothing throws into the host app — dead daemon, wrong token, oversized payloads
tags: [testing, suite, hostile-host, resilience]
status: verified 2026-09-09 auto
source: [CLAUDE.md, README.md, docs/WORKFLOW.md]
as_of: 58f3dad19 2026-09-09
---
The self-contained Pest suite includes a "hostile host" suite that plays the synthetic adversarial host — daemon down, wrong token, oversized payloads — and proves the package does not throw into the host app in any of them. This validates graceful degradation, not active handling: per the cardinal rule, every event hook, sensor call, socket write, and encode is wrapped in try/catch and failures are swallowed (optionally error-logged). Worst case with a dead daemon is ~1 s per digest (0.5 s connect + 0.5 s write timeout) with zero user-visible exceptions; the suite proves the package works silently with no server at all.

**Why:** The un-crashable guarantee is the package's P0 invariant — any code path that can throw into a host app is a P0 bug — and this suite is the regression net for the swallow-all-exceptions behaviour; `docs/WORKFLOW.md` counts it as the project's standing synthetic QA.

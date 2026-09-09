---
title: Package must be uncrashable; all hooks and socket writes are wrapped
tags: [cardinal-rule, reliability, architecture]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md, README.md]
as_of: 16a6c506a 2026-09-06
---
Every event hook, sensor call, socket write, and encode is wrapped in try/catch with failures swallowed. Worst case with a dead daemon is ~1 s per digest (0.5 s connect + 0.5 s write timeout), zero user-visible exceptions. Any code path that can throw into a host app is a P0 bug.

**Why:** This is a composer package installed into production Laravel apps. The host application's stability must never depend on Daywatch telemetry collection succeeding. Telemetry loss is acceptable; host-application impact never is.

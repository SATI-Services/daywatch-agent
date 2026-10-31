---
title: Cardinal rule — the package must be un-crashable; throwing into the host app is a P0
tags: [cardinal, uncrashable, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
Every event hook, sensor call, socket write, and encode is wrapped in try/catch and failures are swallowed (optionally error-logged). `register()` stashes boot exceptions and reports them in `boot()`. Worst case with a dead daemon: ~1 s per digest (0.5 s connect + 0.5 s write timeout), zero user-visible exceptions.

Any code path that can throw into the host app is a P0 bug.

**Why:** The package is installed into monitored Laravel apps and runs inside the host app, so an uncaught exception escaping it surfaces in the host app.

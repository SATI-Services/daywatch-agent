---
title: AuthProbeResult.summary() renders a compact one-liner for the dashboard auth row and the boot log
tags: [auth, daemon, display]
status: verified 2026-10-31 auto
source: [src/Daemon/AuthProbeResult.php]
as_of: fda013bdc 2026-10-31
---
`AuthProbeResult::summary()` renders the state as a compact one-liner for the dashboard's auth row and the boot log; `logLine()` returns the same string prefixed with `[daywatch:agent] auth `.

It carries actionable detail per state (e.g. `check DAYWATCH_TOKEN` for UNAUTHORIZED, `DAYWATCH_TOKEN is empty` for MISSING_TOKEN, `check DAYWATCH_BASE_URL` for NOT_FOUND). The resolved tenancy (`project <id> · <environment>`, set via `withTenancy()`) is appended only in the `ok` state, and only when the ingest resolved one.

**Why:** Operators need a quick diagnosis in the dashboard and logs without reading source.

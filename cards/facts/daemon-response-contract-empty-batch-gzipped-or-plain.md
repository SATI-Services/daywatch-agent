---
title: AuthProbe POSTs an empty {"records":[]} batch, gzipped, at boot
tags: [auth, daemon, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
`Daemon/AuthProbe` POSTs an empty batch — `{"records":[]}` — to `{base_url}/api/ingest` at daemon boot, gzipped, carrying the same headers a real flush carries.

Both ingest implementations (Laravel app and Java relay) answer identically and conclusively: auth precedes all payload handling in each, and an empty records array is a valid batch that stores nothing.

**Why:** An empty batch is the one probe both ingest implementations handle identically without side effects. It carries real auth headers so the result is conclusive: 2xx = token accepted, 401/403 = rejected, 404 = `DAYWATCH_BASE_URL` points at no ingest, 429/503/5xx = up but shedding (token unproven).

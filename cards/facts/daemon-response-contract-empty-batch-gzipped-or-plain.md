---
title: AuthProbe POSTs an empty batch {records:[]} gzipped to {base_url}/api/ingest
tags: [auth, daemon, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
At boot, `Daemon/AuthProbe` POSTs an empty batch — `{"records":[]}`, gzipped — to `{base_url}/api/ingest` to verify token acceptance. The POST carries the same headers a real flush carries.

Both ingest implementations (Laravel app and Java relay) answer identically and conclusively: auth precedes all payload handling in each, and an empty records array is a valid batch that stores nothing (Laravel app → `202 {"accepted":0}` with zero rows RPUSHed; Java relay → same, zero rows enqueued). 2xx = token accepted, 401/403 = rejected, 404 = `DAYWATCH_BASE_URL` points at no ingest, 429/503/5xx = up but shedding (token unproven — the relay also 429s when its own tenancy directory is down), transport rejection = unreachable.

**Why:** An empty batch is the one probe both ingest implementations handle identically without side effects, and it carries real auth headers so the result is conclusive.

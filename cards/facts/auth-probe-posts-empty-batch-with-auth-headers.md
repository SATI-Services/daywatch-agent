---
title: AuthProbe POSTs {records:[]} gzipped with full auth headers to diagnose token acceptance
tags: [daemon, auth, protocol]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: 80c896873 2026-09-09
---
`Daemon/AuthProbe` sends `{"records":[]}` gzipped with identical headers to a real flush. Both ingest implementations (Laravel app and Java relay) handle it identically: 2xx = token accepted, 401/403 = rejected, 404 = no ingest at URL, 429/503/5xx = up but shedding.

**Why:** An empty records array is valid to both implementations and proves auth before any payload handling. No guessing from silent drops.

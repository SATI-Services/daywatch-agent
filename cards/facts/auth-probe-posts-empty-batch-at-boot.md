---
title: AuthProbe POSTs empty batch {records:[]} at boot to verify token acceptance
tags: [daemon, auth, startup, protocol]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
`Daemon/AuthProbe` POSTs an empty batch `{"records":[]}` (gzipped, with real ingest headers) at boot to verify token acceptance. Both ingest implementations answer identically: 2xx = token accepted, 401/403 = rejected, 404 = base_url points at no ingest, 429/503/5xx = shedding (token unproven). On success only, a best-effort `GET {base_url}/api/ingest/tenancy` names the resolved project/environment (exists on Laravel app, NOT on Java relay, so 404 never downgrades the headline result).

**Why:** Catches auth issues at startup instead of silently dropping batches. The empty batch is valid on both ingest implementations, making it a universal probe.

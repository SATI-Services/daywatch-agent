---
title: AuthProbe tenancy lookup is best-effort decoration; a 404 there leaves the result unchanged
tags: [auth, daemon, probe]
status: verified 2026-10-27 auto
source: [src/Daemon/AuthProbe.php]
as_of: 28ce500a6 2026-10-26
---
After a 2xx empty-batch POST to `/api/ingest`, AuthProbe GETs `/api/ingest/tenancy` to name the project/environment. Any non-2xx from that GET — 404 included — leaves the headline result exactly as it was. The endpoint exists on the Laravel app only, not on the relay.

**Why:** Tenancy naming is enrichment only. Probe success is determined solely by the empty-batch POST; the tenancy lookup is best-effort decoration and a 404 there never downgrades the headline result.

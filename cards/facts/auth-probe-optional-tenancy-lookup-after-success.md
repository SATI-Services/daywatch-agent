---
title: After successful auth probe, daemon best-effort GETs /api/ingest/tenancy to name project/environment; endpoint exists on Laravel app only
tags: [daemon, auth, startup]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: c8cd1c517 2026-09-10
---
On a 2xx auth probe response only, the daemon performs a best-effort GET {base_url}/api/ingest/tenancy to obtain the resolved project and environment. That tenancy endpoint exists on the central Laravel app (it backs the relay's token lookup) and does not exist on the Java relay, so its 404 on the relay never downgrades the headline auth success.

**Why:** The GET is diagnostic only (names the tenant on success) and is guarded so it cannot downgrade a conclusive 2xx token acceptance from the probe.

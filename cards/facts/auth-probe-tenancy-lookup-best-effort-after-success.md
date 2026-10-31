---
title: After successful auth probe, daemon best-effort GETs /api/ingest/tenancy to name project/environment; endpoint exists on Laravel app only
tags: [auth, daemon, tenancy]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
On success only, a best-effort `GET {base_url}/api/ingest/tenancy` names the resolved project/environment. That endpoint exists on the Laravel app (it backs the relay's token lookup) and NOT on the relay, so its 404 never downgrades the headline result.

**Why:** Named project/environment is useful context for the operator, but must not affect the auth verdict. The endpoint is best-effort because the relay does not implement it.

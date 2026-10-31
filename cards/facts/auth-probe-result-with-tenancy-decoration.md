---
title: AuthProbeResult.withTenancy() returns a new instance carrying the resolved project and environment
tags: [auth, daemon, tenancy]
status: verified 2026-10-31 auto
source: [src/Daemon/AuthProbeResult.php]
as_of: fda013bdc 2026-10-31
---
`AuthProbeResult::withTenancy(?string $projectId, ?string $environment)` returns a new instance that copies `state`, `detail` and `status` from the original and carries the passed `projectId` and `environment` in place of the original's. The original result is left untouched (the class is immutable), so the result can be decorated after the ingest resolves the token to a tenancy.

**Why:** The result needs to carry tenancy information for display without modifying the original result; `summary()` renders the tenancy on the dashboard's auth row and in the boot log.

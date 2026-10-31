---
title: AuthProbeResult carries seven outcome states, not a bool
tags: [auth, daemon, diagnostics]
status: verified 2026-10-31 auto
source: [src/Daemon/AuthProbeResult.php]
as_of: fda013bdc 2026-10-31
---
`AuthProbeResult`, the outcome of the daemon's startup auth check, holds one of seven `$state` values: `PENDING`, `OK`, `UNAUTHORIZED` (401/403), `MISSING_TOKEN` (no token configured), `NOT_FOUND` (404), `UNVERIFIED` (429/503/5xx — retry-shaped, token unproven) and `UNREACHABLE` (DNS, refused connection, TLS, timeout).

**Why:** An operator must tell "my token is wrong" (fix `DAYWATCH_TOKEN`) apart from "nothing is listening" (start the ingest) apart from "wrong URL" (fix `DAYWATCH_BASE_URL`) apart from "the ingest is up and authenticated me but is shedding load" (nothing to fix) — a bool hides the diagnosis.

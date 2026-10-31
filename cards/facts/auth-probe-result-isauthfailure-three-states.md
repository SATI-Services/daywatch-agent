---
title: AuthProbeResult.isAuthFailure() is true for UNAUTHORIZED, MISSING_TOKEN, NOT_FOUND
tags: [auth, daemon, probe]
status: verified 2026-10-31 auto
source: [src/Daemon/AuthProbeResult.php]
as_of: 52f90abb4 2026-10-31
---
`AuthProbeResult.isAuthFailure()` returns true for exactly three states: `UNAUTHORIZED` (401/403 — token rejected), `MISSING_TOKEN` (no `DAYWATCH_TOKEN` configured), and `NOT_FOUND` (404 — no ingest endpoint at that URL). In those states telemetry will be rejected until an operator acts.

It returns false for `PENDING`, `OK`, `UNVERIFIED` (429/503/5xx — retry-shaped, token unproven), and `UNREACHABLE` (DNS, refused connection, TLS, timeout).

**Why:** Separates failures a retry might clear from permanent ones that need a human to fix `DAYWATCH_TOKEN` or `DAYWATCH_BASE_URL`.

---
title: AuthProbeResult.ok() returns true only when state is OK
tags: [auth, daemon, probe]
status: verified 2026-10-31 auto
source: [src/Daemon/AuthProbeResult.php]
as_of: 52f90abb4 2026-10-31
---
`AuthProbeResult.ok()` is the shorthand predicate for `$this->state === self::OK` — true only when the ingest accepted the token, and nothing else. `PENDING`, `UNVERIFIED`, and `UNREACHABLE` all return false.

**Why:** Lets a caller ask "is telemetry authorised?" without matching against the state constants; the seven-state class exists so an operator can tell a wrong token from nothing listening.

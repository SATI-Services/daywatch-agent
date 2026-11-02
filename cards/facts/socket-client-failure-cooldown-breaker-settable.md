---
title: SocketClient circuit breaker has a configurable failureCooldown window
tags: [daemon, ingest, socket, resilience]
status: verified 2026-11-02 auto
source: [src/Ingest/SocketClient.php]
as_of: f7a73c360 2026-11-02
---
SocketClient accepts a `failureCooldown` parameter (default 0.0, which disables the breaker). When > 0, the breaker suppresses connect attempts for that duration after a failed attempt.

**Why:** Prevents hot retries and observable per-execution cost when the daemon is persistently unreachable; a successful round-trip resets it.

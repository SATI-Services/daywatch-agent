---
title: AuthProbe diagnostic only: promise never rejects, check never blocks loop, failure never stops daemon
tags: [auth, daemon, resilience]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
AuthProbe is diagnostic only: the promise never rejects, the check never blocks the loop, and a failure never stops the daemon (the ingest may just not be up yet — the dispatcher owns retries).

**Why:** The daemon must start and begin receiving records regardless of whether auth succeeds. The ingest may be initializing; the dispatcher owns retry logic.

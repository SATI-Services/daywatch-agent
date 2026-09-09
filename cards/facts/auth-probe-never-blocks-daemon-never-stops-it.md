---
title: AuthProbe promise never rejects; check never blocks loop or stops daemon
tags: [daemon, auth, startup, resilience]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
The `AuthProbe` promise never rejects; the check never blocks the event loop or stops the daemon. Failure is diagnostic only. The ingest may just not be up yet — the dispatcher owns retries.

**Why:** The daemon must start moving records even if auth hasn't been fully verified. Failures surface in logs/dashboard but don't block operation.

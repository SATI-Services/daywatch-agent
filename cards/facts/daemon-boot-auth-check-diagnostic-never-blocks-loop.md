---
title: Auth check at daemon boot is diagnostic only; never blocks loop or stops daemon
tags: [daemon, auth, startup]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: 80c896873 2026-09-09
---
The `Daemon/AuthProbe` runs at boot but never rejects its promise, never blocks the event loop, and never stops the daemon. A failed auth check does not prevent startup.

**Why:** The ingest may not be up yet. Retries are owned by the dispatcher. Diagnostic surfaces appear in the boot log and dashboard, not as fatal errors.

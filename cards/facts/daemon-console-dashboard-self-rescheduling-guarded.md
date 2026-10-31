---
title: ConsoleDashboard self-rescheduling and guarded so rendering failures cannot crash the loop
tags: [daemon, resilience, console]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
`Daemon/ConsoleDashboard` repaints are self-rescheduling and guarded so rendering failures cannot crash the event loop.

**Why:** An exception during dashboard rendering must not crash the daemon or stop record ingestion.

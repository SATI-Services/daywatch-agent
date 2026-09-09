---
title: EADDRINUSE caught at boot and reported with diagnostics and next steps
tags: [daemon, startup, error-handling, diagnostics]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
`EADDRINUSE` (port already in use) is caught at boot and reported as "another daywatch:agent is probably already running", with next-step commands (`daywatch:status`, `lsof -nP -iTCP:{port}`, `--listen=`) instead of leaking the raw ReactPHP socket message.

**Why:** Helpful error messages guide operators to the problem; raw socket errors are cryptic.

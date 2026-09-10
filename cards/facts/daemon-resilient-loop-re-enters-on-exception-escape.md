---
title: Daemon re-enters event loop if exception escapes a callback; bounded against hot re-throw
tags: [daemon, resilience, safety]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: 80c896873 2026-09-09
---
`AgentCommand::runResiliently()` catches any exception that escapes `Loop::run()` and re-enters the loop, so internal errors never kill the daemon. Protection is in place against a hot re-throw loop.

**Why:** The daemon must stay up even when a callback has a bug. Only a deliberate shutdown frame stops it.

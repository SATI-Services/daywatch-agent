---
title: Daemon re-enters resilient loop if exception escapes Loop::run(); bounded against hot re-throw
tags: [daemon, resilience, error-handling, reactor]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
`AgentCommand::runResiliently()` re-enters the ReactPHP event loop if any callback lets an exception escape `Loop::run()`. Re-entry is bounded against a hot re-throw loop, so the daemon never dies on an internal error — only a deliberate shutdown frame stops it.

**Why:** ReactPHP event loops die when a callback throws uncaught. Re-entering with a bounded guard ensures transient errors don't kill the daemon permanently.

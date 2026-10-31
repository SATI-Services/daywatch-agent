---
title: AgentCommand::runResiliently() re-enters event loop if callback lets exception escape; bounded against hot re-throw
tags: [daemon, resilience, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
`AgentCommand::runResiliently()` re-enters the event loop if any callback ever lets an exception escape `Loop::run()`. The re-entry is bounded against a hot re-throw loop so the daemon never dies on an internal error — only a deliberate shutdown frame stops it.

**Why:** A callback exception must not crash the daemon. Re-entering the loop allows the daemon to recover from transient failures.

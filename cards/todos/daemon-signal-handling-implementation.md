---
title: Decide and implement process signal handling (SIGINT/SIGTERM) for daywatch:agent
tags: [daemon, operations, resilience]
status: open
owner: unassigned
since: 2026-09-10
source: [AGENTS.md]
as_of: c8cd1c517 2026-09-10
---
AGENTS.md documents the daemon's resilient loop (AgentCommand::runResiliently) and that "only a deliberate shutdown frame stops it", but it does not describe any OS signal handling (SIGINT/SIGTERM) or how the process should behave on those signals. This todo captures the decision/implementation gap.

What to do: decide whether the daemon should catch SIGINT/SIGTERM and, if so, implement handlers (e.g. in AgentCommand or a Daemon/SignalHandler) that initiate a graceful shutdown sequence. The implementation should specify whether handlers wait for in-flight POSTs to complete, the bounded wait time, and the observable outcomes.

Why open: the repository's engineering guide omits signal handling; without an explicit decision, behavior on external signals is unspecified.

Done looks like: a documented design decision checked into the repo and one of:
- Signal handlers implemented + tests that exercise kill -TERM / Ctrl+C showing the chosen shutdown behavior (with defined wait bounds and no unexpected crashes), or
- A documented rationale for intentionally not handling process signals (and owners/operators informed).

---
title: Implement graceful shutdown on SIGINT/SIGTERM with in-flight batch completion
tags: [daemon, operations, resilience]
status: open
owner: unassigned
since: 2026-09-09
source: [AGENTS.md]
as_of: 80c896873 2026-09-09
---
Add signal handlers to `AgentCommand` or `Daemon/SignalHandler` to catch SIGINT/SIGTERM and initiate a graceful shutdown that waits for all in-flight POST requests to complete before exiting. Done = tests pass, manual `kill -TERM` leaves no dropped batches.

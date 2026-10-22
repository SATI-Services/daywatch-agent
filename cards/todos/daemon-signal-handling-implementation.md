---
title: Decide and implement process signal handling (SIGINT/SIGTERM) for daywatch:agent
tags: [daemon, signals, robustness]
status: open
owner: unassigned
since: 2026-10-21
source: [.claude/agents/agent-package-engineer.md]
as_of: 2fb153d96 2026-10-21
---
The daywatch:agent daemon must handle SIGINT and SIGTERM cleanly: graceful shutdown, in-flight batch completion or flush, resource cleanup. ReactPHP's Loop supports signal handling via `$loop->addSignal()`. Done = signals caught and logged, pending batch handled (flush or complete), and E2E test against `kill -TERM` proves clean shutdown without errors in logs.

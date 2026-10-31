---
title: Daemon POSTs raw HTTP/1.1 over react/socket, never react/http
tags: [daemon, dependencies, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: da814d4f7 2026-10-31
---
The daemon's outbound POST is raw HTTP/1.1 over `react/socket`, NOT `react/http`. `react/http` pins `psr/http-message ^1.0`, which is incompatible with a Laravel 13 host's `^2.0`.

**Why:** Never add `react/http` — it creates a hard PSR-HTTP-Message version conflict with a modern Laravel host.

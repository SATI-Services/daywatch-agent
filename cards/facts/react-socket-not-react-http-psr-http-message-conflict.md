---
title: Daemon POSTs raw HTTP/1.1 over react/socket, never react/http; psr/http-message ^1.0 conflict with Laravel 13 ^2.0
tags: [daemon, dependencies, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
The daemon POSTs raw HTTP/1.1 over `react/socket`, NOT `react/http`. The `react/http` package pins `psr/http-message ^1.0`, which conflicts with a Laravel 13 host's `^2.0`.

The `react/*` daemon deps are pinned tilde-to-locked to their installed minors (patch drift only): `react/{event-loop ~1.6.0, socket ~1.17.0, promise ~3.3.0, stream ~1.4.0}`.

**Why:** Never add `react/http` — it creates a hard PSR-HTTP-Message version conflict with modern Laravel hosts.

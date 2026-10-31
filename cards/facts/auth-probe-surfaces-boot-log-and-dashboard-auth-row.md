---
title: AuthProbe outcome surfaces in boot log and dashboard auth row; gated by DAYWATCH_DAEMON_AUTH_CHECK
tags: [auth, daemon, observability]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
AuthProbe surfaces: one boot log line (`[daywatch:agent] auth …`) and the dashboard's `auth` row. Gated by `daywatch.daemon.auth_check` (env `DAYWATCH_DAEMON_AUTH_CHECK`, default true) / `--no-auth-check`.

**Why:** An operator running the daemon needs immediate, visible feedback on whether the token is accepted before records start flowing.

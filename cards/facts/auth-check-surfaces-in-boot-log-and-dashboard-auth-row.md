---
title: Auth check surfaces in boot log line and dashboard auth row; gated by DAYWATCH_DAEMON_AUTH_CHECK
tags: [daemon, auth, observability, config]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
Auth check surfaces as one boot log line (`[daywatch:agent] auth …`) and the dashboard's `auth` row. Gated by `daywatch.daemon.auth_check` (env `DAYWATCH_DAEMON_AUTH_CHECK`, default true) / `--no-auth-check`.

**Why:** Operators can see auth status on startup and disable the check if the ingest isn't ready yet.

---
title: Upgrade host apps to daywatch/agent 1.3.0 and restart daemons and workers
tags: [release, deploy]
status: open
owner: unassigned
since: 2026-10-12
source: [docs/sessions/ryan/2026-10-12-release-1.3.0.md]
as_of: f7896fb80 2026-10-24
---
Run `composer update daywatch/agent` on all host applications. Restart the `daywatch:agent` daemon (long-running process holds old code) and reload Octane/queue workers (sensor code is per-process). After that, sampled records carry `sample_rate` and dashboard badges appear within minutes; no server redeploy needed.

Done = both daemons and workers restarted and sampling badges visible in dashboard.

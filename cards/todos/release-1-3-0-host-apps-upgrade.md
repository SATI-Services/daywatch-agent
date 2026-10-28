---
title: Upgrade host apps to daywatch/agent 1.3.0 and restart daemons/workers
tags: [release, deployment]
status: open
owner: unassigned
since: 2026-10-12
source: [docs/sessions/ryan/2026-10-12-release-1.3.0.md]
as_of: 397fecced 2026-10-27
---
Host apps (icicle, pingpath) must run `composer update daywatch/agent` and restart `daywatch:agent` daemon and Octane/queue workers. After upgrade, sampled records carry `sample_rate` and dashboard badge appears within minutes. No server redeploy needed (ingest already live). Done = upgrade complete on both hosts, daemons and workers restarted, sampled records carry `sample_rate` in ingest.

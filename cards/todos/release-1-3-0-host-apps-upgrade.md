---
title: Upgrade icicle and pingpath to daywatch/agent 1.3.0, then restart daemon and reload workers
tags: [release, deployment]
status: open
owner: operator
since: 2026-10-12
source: [docs/sessions/ryan/2026-10-12-release-1.3.0.md]
as_of: 397fecced 2026-10-27
---
Both host apps — icicle and pingpath — run agent 1.2.0, which samples (drops data) but never stamps `sample_rate`, so the dashboard badge stays hidden. Run `composer update daywatch/agent` on each, then restart the long-running `daywatch:agent` daemon (it holds the old code) and reload any Octane/queue workers (sensor code is per-process).

After that, sampled records carry `sample_rate` and the dashboard badge (`~N% sample`) appears within minutes. No server redeploy needed — migration 000107 and PR #13 are already live, and stamping is additive-optional so any ingest version tolerates it.

Done = upgrade complete on both hosts, daemon restarted and workers reloaded, sampled records carrying `sample_rate` in ingest.

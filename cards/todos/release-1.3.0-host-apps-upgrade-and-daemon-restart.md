---
title: Upgrade host apps to daywatch/agent 1.3.0 and restart daemons and workers
tags: [release, deploy]
status: open
owner: unassigned
since: 2026-10-12
source: [docs/sessions/ryan/2026-10-12-release-1.3.0.md]
as_of: 50b6411a1 2026-10-21
---
Icicle and pingpath run agent 1.2.0, which samples but never stamps `sample_rate`. Tag 1.3.0 (pushed 2026-10-12) carries effective inclusion-probability stamping. Done = both hosts run `composer update daywatch/agent`, restart `daywatch:agent` daemon and Octane/queue workers, and sampled records carry `sample_rate` on the dashboard badge.

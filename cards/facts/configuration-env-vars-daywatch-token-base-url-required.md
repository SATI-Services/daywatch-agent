---
title: DAYWATCH_TOKEN and DAYWATCH_BASE_URL are required; without them app runs normally collecting nothing
tags: [configuration, secrets, env]
status: verified 2026-09-09 auto
source: [README.md]
as_of: 16a6c506a 2026-09-06
---
`DAYWATCH_TOKEN` and `DAYWATCH_BASE_URL` are required to transmit telemetry. Without them the app runs normally and simply collects nothing. Full option table lives in published `config/daywatch.php`.

**Why:** Graceful degradation: the package never breaks apps. It only acts when configured.

---
title: DAYWATCH_INGEST_URI defaults to 127.0.0.1:2408; use 0.0.0.0:2408 in Docker
tags: [configuration, env, daemon]
status: verified 2026-09-09 auto
source: [README.md]
as_of: 16a6c506a 2026-09-06
---
`DAYWATCH_INGEST_URI` (app → daemon address) defaults to `127.0.0.1:2408`. Use `0.0.0.0:2408` in Docker (when app and daemon are in separate containers).

**Why:** Default assumes localhost daemon; Docker requires all-interfaces bind.

---
title: daywatch:status sends a STATS frame; falls back to PING for older daemons or token mismatch
tags: [daemon, command, protocol]
status: verified 2026-10-29 auto
source: [src/Console/StatusCommand.php]
as_of: 541096adf 2026-10-29
---
StatusCommand.handle() sends a `STATS` frame over the local socket via the bound `Client`. A non-nil stats reply is rendered as a metric table, or raw JSON with `--json`. If the stats reply is nil but `ping()` succeeds, the daemon is up but predates STATS or has a token mismatch — report reachability only. An unreachable daemon prints a message and returns `FAILURE` (exit 1); nothing throws.

**Why:** Older daemon builds don't speak STATS; token mismatch looks like silence to the client.

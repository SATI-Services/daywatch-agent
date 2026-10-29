---
title: StatusCommand renders last_flush_at as UTC time plus age in seconds
tags: [daemon, command, display]
status: verified 2026-10-29 auto
source: [src/Console/StatusCommand.php]
as_of: 541096adf 2026-10-29
---
StatusCommand.format() renders the last_flush_at metric as `YYYY-MM-DD HH:MM:SS UTC (Ns ago)` for numeric values, `never` if null or non-numeric. This applies to the table output only; `--json` prints the raw counters.

**Why:** Ops need both the wall-clock time and how stale the flush is.

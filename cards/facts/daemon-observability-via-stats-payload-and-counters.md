---
title: Daemon observability via STATS payload literal and O(1) counters in DaemonStats
tags: [daemon, observability, stats, monitoring]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
The daemon surfaces observability via an additive `STATS` payload literal (frame version stays `v1`; documented like `PING` in `system/agent-protocol.md` §4). `DaemonStats` keeps O(1) counters: records received/buffered, buffered bytes, batches/records sent, send failures, retries, last flush time/size, and configured base URL. Records are counted ONCE per digest at frame boundary via `RecordCounter` (C-speed string scan honouring JSON strings/escapes).

**Why:** Operators need to verify the daemon is alive and moving records. O(1) counters and STATS as a payload literal avoid re-parsing JSON.

---
title: Daemon gained additive STATS payload literal; frame version stays v1, documented like PING
tags: [daemon, stats, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
An additive `STATS` payload literal was added to the daemon's framing (frame version stays `v1`, documented like `PING` in `system/agent-protocol.md` §4) so operators can see the daemon is alive and moving records. Records are counted ONCE per digest at the frame boundary.

**Why:** The system needs visibility into daemon health and throughput without requiring the full ingest to respond. STATS is operator-facing and additive to the existing protocol.

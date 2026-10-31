---
title: Ingest/SocketClient waits for the daemon's 2:OK ack
tags: [socket, daemon, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
After writing a frame, `src/Ingest/SocketClient.php` waits for the daemon's `2:OK` acknowledgement.

**Why:** The ack is the daemon's acceptance signal; `daywatch:status` uses the same STATS-frame → ack + `{len}:{json}` reply exchange.

---
title: Local-socket frame is {length}:{version}:{token_hash}:{payload}
tags: [daemon, protocol, socket]
status: verified 2026-11-03 auto
source: [src/Daemon/Frame.php]
as_of: 9cfb6374a 2026-11-03
---
A single decoded local-socket frame (agent protocol §4) has the structure `{length}:{version}:{token_hash}:{payload}`.

`payload` is a JSON array of record objects, or a control literal: `PING` (reachability probe) or `STATS` (counters snapshot). `Frame::isPing()` and `Frame::isStats()` test those two literals by exact string match.

**Why:** Not every payload is JSON — a parser that assumes JSON breaks on `PING` and `STATS` frames.

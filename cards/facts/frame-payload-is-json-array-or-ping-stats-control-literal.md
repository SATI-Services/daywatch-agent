---
title: Frame payload is JSON array of records or control literal PING/STATS
tags: [daemon, protocol, socket]
status: verified 2026-11-03 auto
source: [src/Daemon/Frame.php]
as_of: 9cfb6374a 2026-11-03
---
A decoded local-socket frame carries `payload` which is either a JSON array of record objects or a control literal: `PING` (reachability probe) or `STATS` (counters snapshot).

**Why:** Distinguishes data frames from control traffic on a single socket protocol.

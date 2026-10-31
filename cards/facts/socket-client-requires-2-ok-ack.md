---
title: SocketClient waits for a `2:OK` ack after each frame
tags: [daemon, socket, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
After writing a frame, `Ingest/SocketClient.php` waits for a `2:OK` ack from the daemon.

**Why:** It is the daemon's receipt for the frame, distinct from the frame format itself.

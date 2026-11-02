---
title: SocketClient.stats() reads one {length}:{json} mini-frame from the daemon
tags: [daemon, ingest, socket, observability]
status: verified 2026-11-02 auto
source: [src/Ingest/SocketClient.php]
as_of: f7a73c360 2026-11-02
---
SocketClient.stats() sends a STATS frame, awaits the 2:OK ack, then reads one mini-frame with a bounded length prefix (≤10 digits) and payload (≤65,536 bytes). Returns the decoded JSON array or null on any error.

**Why:** Lets the host app query daemon counters without parsing a full protocol frame.

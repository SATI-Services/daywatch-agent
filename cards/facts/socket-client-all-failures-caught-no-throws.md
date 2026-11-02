---
title: SocketClient catches all failures; nothing throws into the host app
tags: [daemon, ingest, socket, resilience]
status: verified 2026-11-02 auto
source: [src/Ingest/SocketClient.php]
as_of: f7a73c360 2026-11-02
---
Every failure mode — dead daemon, refused connection, timeout, socket closed mid-write, wrong ack, malformed STATS reply — returns false/null instead of throwing: transmit() and stats() wrap the whole attempt in `catch (Throwable)`. send() and ping() return bool; stats() returns the decoded counters array or null.

**Why:** Cardinal rule: the package must be un-crashable; sending digests can never fault the host app.

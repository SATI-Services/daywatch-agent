---
title: SocketHttpSender applies a 10 second timeout per HTTP request by default
tags: [daemon, http, timeout]
status: verified 2026-11-02 auto
source: [src/Daemon/SocketHttpSender.php]
as_of: a96ebd137 2026-11-01
---
The constructor takes a `float $timeout` parameter defaulting to `10.0` seconds. Each `send()` registers a loop timer for that duration; if no response has arrived when it fires, the returned promise rejects with `RuntimeException('ingest request timed out')`. The timeout rejects the promise only — it does not close the connection, which is closed from the `end`/`close` handlers.

**Why:** A hung ingest request cannot hold its promise open indefinitely.

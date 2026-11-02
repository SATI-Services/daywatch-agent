---
title: ConnectionHandler.respondWithStats() swallows and logs stats responder and reply-write failures
tags: [daemon, stats, robustness]
status: verified 2026-11-02 auto
source: [src/Daemon/ConnectionHandler.php]
as_of: a96ebd137 2026-11-01
---
`ConnectionHandler.respondWithStats()` wraps the `statsResponder` call and the stats reply write in one try-catch. A throw from either is logged as `stats reply failed: <message>` and swallowed, so the event loop continues. If the responder returns a non-string or an empty string, no stats reply is sent and nothing is logged.

**Why:** Protects the event loop. A failing stats computation or stats reply write cannot crash the daemon.

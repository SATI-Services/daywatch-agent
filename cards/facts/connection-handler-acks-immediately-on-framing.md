---
title: ConnectionHandler ACKs frame receipt immediately, before auth or version check
tags: [daemon, protocol, framing]
status: verified 2026-11-02 auto
source: [src/Daemon/ConnectionHandler.php]
as_of: a96ebd137 2026-11-01
---
`ConnectionHandler::feed()` writes the `2:OK` ack as soon as `FrameParser` yields a complete frame, before `process()` runs the version check or the token-hash comparison. A frame with an unknown version or a token-hash mismatch still gets the ack first: unknown version → log + final digest + graceful shutdown; token mismatch → log + drop.

Malformed framing yields no frame, so no ack — the parser's error path closes the connection.

**Why:** Separates framing concerns from processing concerns. The sender knows its bytes arrived; the daemon then decides what to do with them.

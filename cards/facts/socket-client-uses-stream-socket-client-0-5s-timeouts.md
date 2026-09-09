---
title: SocketClient uses stream_socket_client with 0.5s timeouts; {len}:v1:{hash}:{json} frame
tags: [socket, ingest, protocol, framing]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
`src/Ingest/SocketClient.php` uses `stream_socket_client` with 0.5 s timeouts (0.5 s connect, 0.5 s write), sends `{len}:v1:{hash}:{json}` frames, and expects a `2:OK` ack. Stats are available via `stats()`.

**Why:** The 0.5 s per operation ensures a dead daemon never blocks user requests beyond 1 s total (connect + write). Framing with hash enables frame integrity checks on the daemon.

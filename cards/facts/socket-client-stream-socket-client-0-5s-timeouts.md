---
title: Ingest/SocketClient uses stream_socket_client with 0.5 s timeouts
tags: [socket, daemon, gotcha]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
`src/Ingest/SocketClient.php` connects with `stream_socket_client` and 0.5 s timeouts. Worst case with a dead daemon is ~1 s per digest (0.5 s connect + 0.5 s write timeout) and zero user-visible exceptions.

---
title: SocketHttpSender uses a single Connection: close request/response cycle, no keep-alive
tags: [daemon, http]
status: verified 2026-11-02 auto
source: [src/Daemon/SocketHttpSender.php]
as_of: a96ebd137 2026-11-01
---
SocketHttpSender sends one `Connection: close` HTTP/1.1 request per `send()` call, waits for the connection to end or close (the full response), then closes. No keep-alive, no connection pooling, no chunked framing to unpack.

**Why:** It deliberately avoids react/http: every reactphp/http release hard-pins `psr/http-message ^1.0`, which would downgrade or conflict with a Laravel 13 host on `psr/http-message ^2.0`. react/socket carries no psr/http-message dependency, so the package stays installable everywhere.

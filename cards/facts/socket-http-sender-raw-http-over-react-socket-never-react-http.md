---
title: SocketHttpSender uses raw HTTP/1.1 over react/socket, never react/http
tags: [daemon, http, deps]
status: verified 2026-11-04 auto
source: [src/Daemon/Contracts/HttpSender.php]
as_of: 2c6c843ba 2026-11-03
---
SocketHttpSender sends raw HTTP/1.1 over react/socket. Never use react/http, which pins psr/http-message < 2.0 and breaks Laravel 13 host compatibility.

**Why:** Laravel 13 requires psr/http-message ^2.0; react/http's constraint creates an unresolvable conflict. Raw HTTP/1.1 over react/socket avoids the dependency entirely.

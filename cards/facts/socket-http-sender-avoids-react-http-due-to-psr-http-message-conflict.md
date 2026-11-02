---
title: SocketHttpSender avoids react/http to prevent psr/http-message ^1.0 conflict with Laravel 13
tags: [daemon, http, dependencies]
status: verified 2026-11-02 auto
source: [src/Daemon/SocketHttpSender.php]
as_of: a96ebd137 2026-11-01
---
SocketHttpSender implements HTTP/1.1 over react/socket only, never using react/http. Every react/http release hard-pins psr/http-message ^1.0, which conflicts with Laravel 13's psr/http-message ^2.0 constraint.

**Why:** Keeps the package installable on Laravel 13. react/socket carries no psr/http-message dependency.

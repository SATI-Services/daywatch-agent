---
title: Tests stub HttpSender with resolved/rejected promises, no network I/O
tags: [daemon, testing, retry]
status: verified 2026-11-04 auto
source: [src/Daemon/Contracts/HttpSender.php]
as_of: 2c6c843ba 2026-11-03
---
Tests stub the `HttpSender` interface with resolved/rejected promises so the retry/pause logic is exercised with no I/O.

**Why:** `HttpSender` is the daemon's outbound HTTP transport; production uses `SocketHttpSender`. `send(string $method, string $url, array $headers, string $body)` returns `PromiseInterface<HttpResponse>` and rejects on network error.

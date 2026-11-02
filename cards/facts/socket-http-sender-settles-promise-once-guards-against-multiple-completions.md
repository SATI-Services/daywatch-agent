---
title: SocketHttpSender settles its promise once and guards against multiple completion paths
tags: [daemon, http, robustness]
status: verified 2026-11-02 auto
source: [src/Daemon/SocketHttpSender.php]
as_of: a96ebd137 2026-11-01
---
SocketHttpSender uses a `$settled` flag to ensure the Deferred is resolved or rejected exactly once, regardless of whether the connection ends, closes, errors, or times out.

**Why:** ReactPHP connections can emit multiple events (end, close, error); the settled flag prevents double-settling exceptions.

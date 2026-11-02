---
title: ConnectionHandler replies to a STATS frame with one {length}:{json} counters mini-frame
tags: [daemon, protocol, stats]
status: verified 2026-11-02 auto
source: [src/Daemon/ConnectionHandler.php]
as_of: a96ebd137 2026-11-01
---
For a STATS frame that passes the version and token-hash checks, ConnectionHandler calls the `statsResponder` callback; if it returns a non-empty string, the handler writes that string back as one `{length}:{json}` mini-frame — the same shape as the regular ack (agent protocol §4). The per-frame `2:OK` ack is written first, and responder or write failures are logged and swallowed.

**Why:** Lets the client read daemon counters over the existing protocol instead of a separate one.

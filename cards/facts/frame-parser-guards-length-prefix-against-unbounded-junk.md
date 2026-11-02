---
title: FrameParser errors the connection when a length prefix runs past 20 bytes with no colon
tags: [daemon, protocol, framing, robustness]
status: verified 2026-11-02 auto
source: [src/Daemon/FrameParser.php]
as_of: a96ebd137 2026-11-01
---
In `FrameParser::push()`, if the buffer holds more than 20 bytes and still contains no `:`, the parser calls `fail('frame length prefix too long / not numeric')`: it sets the errored flag, records the message, clears the buffer, and returns no frames. It never throws — the caller checks `errored()` and drops the connection.

**Why:** Bounds memory exposure from malformed framing. A client sending only non-numeric junk forever cannot exhaust the daemon's buffer.

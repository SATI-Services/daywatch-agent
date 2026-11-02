---
title: FrameParser never throws; framing errors set an unrecoverable error flag
tags: [daemon, protocol, robustness]
status: verified 2026-11-02 auto
source: [src/Daemon/FrameParser.php]
as_of: a96ebd137 2026-11-01
---
FrameParser never throws. A non-numeric length prefix, a run of more than 20 bytes with no colon, or a declared length above `MAX_FRAME_BYTES` (67,108,864) calls `fail()`: `$errored = true`, the message goes into `$errorMessage`, and the buffer is cleared. The caller checks `errored()` and drops the connection. An incomplete frame is not an error — `push()` simply waits for more chunks. A body that cannot be split into `{version}:{token_hash}:{payload}` is dropped by `decode()` without setting the flag.

**Why:** Protects the event loop — a hostile client cannot blow up the daemon through framing errors.

---
title: FrameParser MAX_FRAME_BYTES is 67,108,864 bytes, matching the 64 MB batch cap
tags: [daemon, protocol, framing, limits]
status: verified 2026-11-02 auto
source: [src/Daemon/FrameParser.php]
as_of: a96ebd137 2026-11-01
---
FrameParser.MAX_FRAME_BYTES = 67,108,864 bytes. A frame whose declared length exceeds this makes the parser set an unrecoverable error flag with the message `frame length exceeds maximum`; the parser never throws and the caller drops the connection.

**Why:** The constant's doc comment says it matches the 64 MB inflated batch cap.

---
title: FrameParser.decode() splits {version}:{token_hash}:{payload} on first two colons only
tags: [daemon, protocol, framing]
status: verified 2026-11-02 auto
source: [src/Daemon/FrameParser.php]
as_of: a96ebd137 2026-11-01
---
FrameParser.decode() finds the first colon to extract version, then the second colon to extract token_hash. Everything after the second colon, including any further colons, is the payload.

**Why:** Allows payloads to contain arbitrary colons without breaking the frame structure.

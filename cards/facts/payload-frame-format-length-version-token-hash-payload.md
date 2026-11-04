---
title: Local socket frame format is {length}:{version}:{token_hash}:{payload}
tags: [daemon, protocol, wire-contract]
status: verified 2026-11-04 auto
source: [src/Ingest/Payload.php]
as_of: f7f795e35 2026-11-04
---
Socket frame wire format: `{length}:{version}:{token_hash}:{payload}` where `length` is the byte length of everything after the first colon, `version` is literally `v1`, and `token_hash` is the first 7 chars of xxh128(token).

**Why:** WIRE CONTRACT — both client and daemon must encode/decode identically.

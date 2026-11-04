---
title: Token hash is the first 7 characters of xxh128(token)
tags: [daemon, protocol, auth]
status: verified 2026-11-04 auto
source: [src/Ingest/Payload.php]
as_of: f7f795e35 2026-11-04
---
`token_hash` is `substr(hash('xxh128', (string) $token), 0, 7)` — both sides derive this same 7-char hash from the shared token.

**Why:** It is part of the wire contract (agent protocol §4): the frame is `{length}:{version}:{token_hash}:{payload}`.

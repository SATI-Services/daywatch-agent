---
title: Payload::ACK is the literal string '2:OK'
tags: [daemon, protocol, frame]
status: verified 2026-11-04 auto
source: [src/Ingest/Payload.php]
as_of: f7f795e35 2026-11-04
---
`Daywatch\Agent\Ingest\Payload::ACK` is the constant `'2:OK'`.

**Why:** It lives in the class that documents the local socket frame format (agent protocol §4) — `{length}:{version}:{token_hash}:{payload}`, version `v1` — flagged as WIRE CONTRACT. Note `'2:OK'` is not itself a full frame: it carries no `version`/`token_hash` segment.

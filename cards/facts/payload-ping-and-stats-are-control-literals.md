---
title: PING and STATS are Payload literals sent as the frame payload
tags: [daemon, protocol, control]
status: verified 2026-11-05 auto
source: [src/Ingest/Payload.php]
as_of: f7f795e35 2026-11-04
---
`Daywatch\Agent\Ingest\Payload` defines `PING = 'PING'` and `STATS = 'STATS'`; each is a literal payload value for the socket frame `{length}:{version}:{token_hash}:{payload}` (agent protocol §4).

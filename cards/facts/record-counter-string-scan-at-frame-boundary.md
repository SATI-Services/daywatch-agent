---
title: RecordCounter counts records via string scan at frame boundary, never decode
tags: [daemon, stats, performance]
status: verified 2026-11-01 auto
source: [src/Daemon/IngestServer.php]
as_of: 2ae2d15e3 2026-10-31
---
`IngestServer::ingest()` counts each incoming digest once, at the frame boundary, with a string scan — `RecordCounter::count($payload)` — never with a JSON decode. Counting runs only when the optional `DaemonStats` dependency is set; otherwise the count is 0.

**Why:** The record count is taken before the payload is buffered, so the STATS counters get it without re-parsing the payload.

---

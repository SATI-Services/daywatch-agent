---
title: ConnectionHandler hands validated record payloads to a RecordSink (IngestServer)
tags: [daemon, socket, protocol]
status: verified 2026-11-04 auto
source: [src/Daemon/Contracts/RecordSink.php]
as_of: 2c6c843ba 2026-11-03
---
`RecordSink` is the interface a `ConnectionHandler` hands validated record payloads to; `IngestServer` implements it. Tests stub it to assert exactly what a connection forwards.

`ingest(string $payload)` accepts one digest payload, a `[...]` JSON array string.

**Why:** Because it is an interface, tests can stub the sink and assert exactly what each connection forwards.

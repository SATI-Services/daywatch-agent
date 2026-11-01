---
title: IngestServer swallows exceptions in ingest(), tick(), and flushNow()
tags: [daemon, reliability]
status: verified 2026-11-01 auto
source: [src/Daemon/IngestServer.php]
as_of: 2ae2d15e3 2026-10-31
---
`ingest()`, `tick()`, and `flushNow()` each wrap their whole body in `try { … } catch (Throwable) {}`. No exception escapes `IngestServer`. `finalDigest()` has no try-catch of its own but only calls the guarded `flushNow()`.

**Why:** `flushNow()` swallows before returning, so a failure in `buffer->flush()` or `dispatcher->dispatch()` never reaches the catch blocks in `ingest()`/`tick()` — buffer, stats and dispatcher errors are isolated and never crash the daemon.

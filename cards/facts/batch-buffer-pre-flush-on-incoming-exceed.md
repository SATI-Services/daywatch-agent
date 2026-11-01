---
title: IngestServer pre-flushes when incoming digest would exceed buffer ceiling
tags: [daemon, batching]
status: verified 2026-11-01 auto
source: [src/Daemon/IngestServer.php]
as_of: 2ae2d15e3 2026-10-31
---
Before adding a new digest, IngestServer calls `buffer.wouldExceed()` to check if appending it would push past the buffer's size ceiling. If so, it flushes the current buffer before adding the new one.

**Why:** Pre-flush so a large incoming digest doesn't blow past the ceiling (docs §5). The age-based (10 s) drain is a separate trigger, via `tick()`.

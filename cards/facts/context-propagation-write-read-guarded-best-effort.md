---
title: ContextPropagation.write() and read() are guarded; propagation failures never throw
tags: [queue, propagation, safety]
status: verified 2026-11-11 auto
source: [src/Support/ContextPropagation.php]
as_of: b92d114da 2026-11-10
---
ContextPropagation.write() and read() wrap all Context access in try/catch. Missing Context class, unbootable Context, or any Throwable in read/write is silently swallowed. read() returns a default array with null values on any failure.

**Why:** Context may be unavailable or unbootable; telemetry propagation must never throw into the host app.

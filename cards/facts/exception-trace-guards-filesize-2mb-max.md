---
title: ExceptionTrace.readContext() returns null for files larger than 2,000,000 bytes
tags: [exception, records, safety]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionTrace.php]
as_of: 2cf41c87a 2026-11-08
---
`ExceptionTrace::readContext()` returns null — so that frame gets no inlined source — when `filesize($file)` is strictly greater than the `MAX_FILE_BYTES` constant, set to `2_000_000` bytes.

**Why:** The guard lives in the frame-context reader, not the caller, so an oversized app file yields a frame whose `code` is null rather than failing the trace build.

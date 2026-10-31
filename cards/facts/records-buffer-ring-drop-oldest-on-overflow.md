---
title: RecordsBuffer.trimToLimit() ring-drops oldest records until both bounds satisfied
tags: [buffer, overflow]
status: verified 2026-10-31 auto
source: [src/Buffer/RecordsBuffer.php]
as_of: fda013bdc 2026-10-31
---
RecordsBuffer.trimToLimit() bounds memory when digesting is disabled by ring-dropping the oldest records until both bounds are satisfied: the record count (`event_buffer`, default 500) and the approximate byte size (`buffer_bytes`, default 5,000,000; a `byteLimit` of 0 disables the byte bound). A single record larger than the byte limit is kept (dropping it would leave the buffer empty and the loop spinning) — the count bound still applies.

**Why:** When digesting is disabled the buffer must not grow unbounded.

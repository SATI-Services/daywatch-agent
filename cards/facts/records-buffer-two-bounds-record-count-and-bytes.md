---
title: RecordsBuffer enforces two bounds: record count (≤500) AND byte size (≤5MB default)
tags: [buffer, bounds, memory]
status: verified 2026-10-31 auto
source: [src/Buffer/RecordsBuffer.php]
as_of: fda013bdc 2026-10-31
---
RecordsBuffer holds two independent bounds: a record count limit (default 500, via `$limit`) and an approximate byte-size limit (default 5,000,000 bytes, via `$byteLimit`). The buffer is full when either bound is reached. Byte bound can be disabled by setting `$byteLimit` to 0, leaving only the count bound.

**Why:** A single record can carry megabytes (e.g., a `sql` string up to 16 MB); 500 records alone is not a memory bound. The byte bound is what actually keeps the host's footprint predictable.

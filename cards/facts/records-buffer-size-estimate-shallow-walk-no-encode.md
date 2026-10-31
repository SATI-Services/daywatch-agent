---
title: RecordsBuffer estimates record size via shallow walk, not json_encode
tags: [buffer, performance]
status: verified 2026-10-31 auto
source: [src/Buffer/RecordsBuffer.php]
as_of: fda013bdc 2026-10-31
---
RecordsBuffer.sizeOf() estimates encoded size by a single shallow walk: string lengths plus a fixed allowance per key for scalars and punctuation. It does not call json_encode — that would double the cost on the hot path. The estimate only needs to track magnitude for the byte bound to work.

**Why:** Performance on the hot path; exact encoding size is not needed, only order of magnitude.

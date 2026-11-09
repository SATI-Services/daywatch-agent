---
title: OutgoingRequestRecord _group is xxh128(host)
tags: [wire-contract, records, grouping]
status: verified 2026-11-09 auto
source: [src/Records/OutgoingRequestRecord.php]
as_of: 20af138a0 2026-11-09
---
`OutgoingRequestRecord._group` is `xxh128(host)`, computed via `Group::host($this->host)`.

**Why:** Grouping by host aggregates all requests to the same remote.

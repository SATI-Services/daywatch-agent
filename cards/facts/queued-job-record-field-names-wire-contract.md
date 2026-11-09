---
title: QueuedJobRecord field names are wire contract
tags: [wire-contract, records]
status: verified 2026-11-09 auto
source: [src/Records/QueuedJobRecord.php]
as_of: 20af138a0 2026-11-09
---
`QueuedJobRecord` (agent protocol §2 — the `queued-job` record) marks its field names as WIRE CONTRACT. `toArray()` emits the `queued-job` envelope child with `_group = xxh128(name)` plus `job_id`, `name`, `connection`, `queue`, `duration`.

**Why:** The class docblock states field names are WIRE CONTRACT, so adding or renaming a key changes the emitted payload.

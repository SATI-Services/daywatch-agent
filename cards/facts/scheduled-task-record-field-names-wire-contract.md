---
title: ScheduledTaskRecord field names are wire contract
tags: [wire-contract, records]
status: verified 2026-11-10 auto
source: [src/Records/ScheduledTaskRecord.php]
as_of: 72d7040e7 2026-11-09
---
`ScheduledTaskRecord` field names are wire contract (agent protocol §2) — do not add or rename them.

**Why:** `toArray()` emits the on-wire format and the class docblock marks the field names as WIRE CONTRACT.

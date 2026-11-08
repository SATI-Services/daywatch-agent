---
title: RequestRecord field names are wire contract — do not add or rename without the daywatch-payloads skill
tags: [wire, record, contract]
status: verified 2026-11-08 auto
source: [src/Records/RequestRecord.php]
as_of: c34aba0b3 2026-11-08
---
`RequestRecord` field names — the keys `toArray()` emits — are wire contract. Do not add or rename them without the `daywatch-payloads` skill.

**Why:** Record field names define the wire payload shape.

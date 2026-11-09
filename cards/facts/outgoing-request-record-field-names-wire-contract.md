---
title: OutgoingRequestRecord field names are wire contract
tags: [wire-contract, records]
status: verified 2026-11-09 auto
source: [src/Records/OutgoingRequestRecord.php]
as_of: 20af138a0 2026-11-09
---
`OutgoingRequestRecord` field names are the wire contract — do not add or rename them.

**Why:** The class docblock declares the field names WIRE CONTRACT; the record is the `outgoing-request` record of the agent protocol (§2), serialised via `toArray()`.

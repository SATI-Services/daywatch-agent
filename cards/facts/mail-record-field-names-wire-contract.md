---
title: MailRecord field names are wire contract
tags: [wire-contract, records]
status: verified 2026-11-09 auto
source: [src/Records/MailRecord.php]
as_of: a6c4bee62 2026-11-08
---
`MailRecord` field names are wire contract: adding or renaming a field changes the `mail` record emitted to agent protocol §2.

**Why:** The class docblock marks the field names as WIRE CONTRACT, and `toArray()` emits them for the `mail` record.

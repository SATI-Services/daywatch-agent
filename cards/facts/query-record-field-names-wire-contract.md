---
title: QueryRecord field names are wire contract
tags: [wire-contract, records]
status: verified 2026-11-09 auto
source: [src/Records/QueryRecord.php]
as_of: c17c1bfe5 2026-11-09
---
`QueryRecord` carries `sql`, `file`, `line`, `duration`, `connection`, `connection_type` on top of the envelope's child head. Its docblock states the field names are WIRE CONTRACT.

**Why:** The docblock points at the published spec, daywatch/docs/data-model.md §2 — the record shape is a documented contract, not an implementation detail, so a rename or an added field changes what the ingest side decodes.

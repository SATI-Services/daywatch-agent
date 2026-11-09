---
title: LogRecord field names are wire contract
tags: [wire-contract, records, log]
status: verified 2026-11-09 auto
source: [src/Records/LogRecord.php]
as_of: c71c5729f 2026-11-09
---
`LogRecord`'s field names (`level`, `message`, `context`, `extra`) are WIRE CONTRACT for the `log` record (agent protocol §2) — do not add or rename them.

**Why:** The class docblock flags the field names as WIRE CONTRACT, so an emit-side change silently breaks the payload the record serialises to.

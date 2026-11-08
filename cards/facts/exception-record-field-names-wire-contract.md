---
title: Exception record field names are wire contract
tags: [wire-contract, exception, records]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionRecord.php]
as_of: 073ae2e05 2026-11-07
---
`ExceptionRecord::toArray()` emits the `exception` payload fields `class`, `file`, `line`, `message`, `code`, `trace`, `handled`, `php_version`, `laravel_version`, merged with the envelope's `_group` (the Issue key, `xxh128(class|code|file|line)`). Field names are the wire contract.

**Why:** The `exception` record is specified in `daywatch/docs/data-model.md` §2, and its field names are the wire contract - a rename changes the emitted payload schema.

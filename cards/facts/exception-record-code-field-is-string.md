---
title: ExceptionRecord code field is a string, not numeric
tags: [wire-contract, exception, records]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionRecord.php]
as_of: 073ae2e05 2026-11-07
---
`ExceptionRecord::$code` is typed `string` and is emitted as `code` in `toArray()`, so the `exception` record's `code` is a string on the wire, not numeric. `line` stays `int`; the class docblock states "`code` is a string" and that field names are WIRE CONTRACT.

**Why:** Consumers must read `code` as a string rather than assuming a numeric code.

---

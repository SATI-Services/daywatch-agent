---
title: LogRecord carries no `_group` field
tags: [records, wire-contract]
status: verified 2026-11-09 auto
source: [src/Records/LogRecord.php]
as_of: a6c4bee62 2026-11-08
---
The `log` record (`LogRecord`, agent protocol §2) has no `_group` field. Its fields are `level`, `message`, `context` and `extra`, and field names are wire contract; `message` has its placeholders interpolated while `context` and `extra` are JSON strings.

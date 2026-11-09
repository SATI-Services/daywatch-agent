---
title: LogRecord context and extra fields are JSON strings
tags: [records, wire-contract]
status: verified 2026-11-09 auto
source: [src/Records/LogRecord.php]
as_of: a6c4bee62 2026-11-08
---
LogRecord `context` and `extra` fields are transmitted as JSON strings, not objects.

**Why:** Preserves structure while keeping record shape flat for emission.

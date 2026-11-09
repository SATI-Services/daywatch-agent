---
title: LogRecord message has placeholders interpolated
tags: [records, wire-contract]
status: verified 2026-11-09 auto
source: [src/Records/LogRecord.php]
as_of: a6c4bee62 2026-11-08
---
LogRecord `message` field carries the full text with all placeholders already interpolated.

**Why:** The ingest receives complete, readable log lines without needing context to reconstruct them.

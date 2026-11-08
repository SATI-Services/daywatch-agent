---
title: ExceptionRecord trace is a JSON string of source-inlined frames
tags: [wire-contract, exception, records]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionRecord.php]
as_of: f86878bca 2026-11-08
---
`ExceptionRecord::$trace` is a JSON string of source-inlined stack frames. `toArray()` emits it as the `trace` key via `Truncate::medium()`.

**Why:** The trace shape is fixed in the record docblock against daywatch/docs/data-model.md §2 — the field carries source-inlined frames as JSON text, and truncation happens at emit time.

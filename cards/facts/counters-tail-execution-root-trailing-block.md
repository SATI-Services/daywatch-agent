---
title: Counters::tail() renders the shared trailing block of every execution-root record
tags: [wire, records, counters]
status: verified 2026-11-08 auto
source: [src/Records/Counters.php]
as_of: 16b149588 2026-11-08
---
Counters::tail(counters, peakMemoryUsage, exceptionPreview, context) returns the trailing block: twelve counters normalized, peak_memory_usage, exception_preview (tiny-truncated), context (text-truncated).

**Why:** Wire contract — the shared tail block is used by request, job-attempt, command, and scheduled-task records.

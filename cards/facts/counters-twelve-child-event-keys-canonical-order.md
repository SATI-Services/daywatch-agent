---
title: Counters carries twelve child-event keys in canonical order
tags: [wire, records, counters]
status: verified 2026-11-08 auto
source: [src/Records/Counters.php]
as_of: 16b149588 2026-11-08
---
Counters::KEYS holds twelve keys in canonical order: exceptions, logs, queries, lazy_loads, jobs_queued, mail, notifications, outgoing_requests, files_read, files_written, cache_events, hydrated_models.

**Why:** The twelve counters are ALWAYS present on the wire in this order; wire contract.

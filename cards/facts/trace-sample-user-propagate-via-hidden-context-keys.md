---
title: Trace/sample/user propagate to queue hops via hidden Context keys
tags: [context, queue, propagation, protocol]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
Trace ID, sampling decision, and user ID propagate to queue hops via hidden Laravel Context keys: `daywatch_trace_id`, `daywatch_should_sample`, `daywatch_user_id`.

**Why:** Queued jobs need to inherit the parent execution's trace context so the full causality chain is preserved in the ingest.

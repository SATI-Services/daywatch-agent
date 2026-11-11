---
title: ContextPropagation propagates trace_id, should_sample, sample_rate, user_id via hidden Context keys
tags: [queue, propagation, context]
status: verified 2026-11-11 auto
source: [src/Support/ContextPropagation.php]
as_of: b92d114da 2026-11-10
---
ContextPropagation writes and reads four hidden Laravel Context keys: `daywatch_trace_id`, `daywatch_should_sample`, `daywatch_sample_rate`, `daywatch_user_id`. Hidden keys ride with serialized jobs across queue hops with zero payload parsing.

**Why:** Traces span queue hops; sampling decision and effective rate propagate so child executions inherit parent context without parsing the job payload.

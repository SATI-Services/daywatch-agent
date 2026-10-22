---
title: Queue hops adopt sample_rate via hidden daywatch_sample_rate Context key, read validates (0,1]
tags: [sampling, propagation, context]
status: verified 2026-10-22 auto
source: [docs/sessions/ryan/2026-10-12-sample-rate-stamping.md]
as_of: 2fb153d96 2026-10-21
---
`src/Support/ContextPropagation.php` carries a new hidden key `daywatch_sample_rate` so queue jobs weight identically to their upstream execution. Read validation ensures the value is in the range (0,1]; any other value reads as null and defaults to 1.0. Older upstream agents that do not write this key are compatible (they simply omit it).

**Why:** A sampled request's queued jobs must not extrapolate against the job rate; they inherit the sampling weight from their source.

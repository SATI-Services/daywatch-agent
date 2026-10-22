---
title: Core.effectiveSampleRate stamps records at digest time; product of head and per-sensor rates
tags: [sampling, emit, core]
status: verified 2026-10-22 auto
source: [docs/sessions/ryan/2026-10-12-sample-rate-stamping.md]
as_of: 2fb153d96 2026-10-21
---
`Core.effectiveSampleRate` captures the sampling decision that won: the head rate at request/command/scheduled-task entry; the upstream rate adopted from propagation; the rescue rate (not the head rate) when `recordException()` re-rolls a sampled-out execution; and 1.0 for forced samples via `Daywatch::sample()`. At digest time, `Core.digest()` stamps every record with the product of the effective head rate and, for the seven rate-gated child types (`RATE_GATED_TYPES`), the per-sensor roll.

The field is omitted when the product is 1.0; `Core.reset()` restores 1.0 at the worker boundary.

**Why:** Sampling rates must reach the server payload so downstream can extrapolate and weight results correctly.

---
title: Every digested record carries sample_rate stamping its effective inclusion probability
tags: [sampling, wire-contract]
status: verified 2026-10-20 auto
source: [docs/sessions/status/ryan.md]
as_of: 6866d739e 2026-10-12
---
Every digested record stamps `sample_rate`: the effective inclusion probability (winning roll's rate × per-sensor roll for seven gated child types; rescue stamps the exceptions rate; force/`sample()` stamps 1.0 ≡ omitted). Propagation gained `daywatch_sample_rate` context key. Release 1.3.0 tagged 2026-10-12.

**Why:** Unblocks dashboard badges for hosts on 1.2.0 (icicle, pingpath) — 1.2.0 samples but never stamps.

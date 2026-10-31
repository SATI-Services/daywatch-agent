---
title: Both ingest implementations (Laravel app and Java relay) answer empty batch identically
tags: [ingest, wire-contract, auth]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
The daemon POSTs to either ingest implementation — the central Laravel app (default + reference, `POST /api/ingest`) or the Java relay (high-throughput alternative) — selected purely by `DAYWATCH_BASE_URL`. The package never knows or cares which.

An empty batch is the minimal probe both answer identically and conclusively: auth precedes all payload handling in each, and an empty records array is a valid batch that stores nothing (Laravel app → `202 {"accepted":0}` with zero rows RPUSHed; Java relay → same, zero rows enqueued).

**Why:** The daemon cannot know which ingest backend answers its requests. A probe that both answer identically ensures AuthProbe works against either implementation.

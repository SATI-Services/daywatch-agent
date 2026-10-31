---
title: AuthProbe conclusion: 2xx = token accepted, 401/403 = rejected, 404 = base_url points at no ingest
tags: [auth, daemon, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
AuthProbe empty-batch result: 2xx = token accepted, 401/403 = rejected, 404 = `DAYWATCH_BASE_URL` points at no ingest, 429/503/5xx = up but shedding (token unproven — the relay also 429s when its own tenancy directory is down), transport rejection = unreachable.

**Why:** An empty batch is the minimal probe both ingest implementations handle identically without side effects, so the result is conclusive and actionable.

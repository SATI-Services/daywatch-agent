---
title: Auth state kept off STATS toArray() — frozen wire contract
tags: [daemon, auth, observability, protocol]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
Auth state (from `DaemonStats::authProbed()/authState()/authSummary()`) is deliberately kept off `STATS.toArray()`, which is the frozen wire contract. Exposing it to `daywatch:status` would be a docs-first cross-repo change via the `daywatch-payloads` skill, not a package-local addition.

**Why:** The STATS wire format is shared across repos. Changes to it must be coordinated via the payloads skill and system docs.

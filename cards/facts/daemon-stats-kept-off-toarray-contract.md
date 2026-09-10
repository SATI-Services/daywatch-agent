---
title: Auth state and auth failures kept off STATS toArray() — frozen wire contract
tags: [daemon, stats, protocol]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: 80c896873 2026-09-09
---
`DaemonStats::authProbed()`, `authState()`, `authSummary()`, and `authFailed()` are diagnostic and kept deliberately off the `toArray()` output — that array is the frozen STATS wire contract. Exposing auth state to `daywatch:status` would require a docs-first cross-repo change via the `daywatch-payloads` skill.

**Why:** The wire contract must be stable. Auth diagnostics are for the dashboard and logs, not the public API.

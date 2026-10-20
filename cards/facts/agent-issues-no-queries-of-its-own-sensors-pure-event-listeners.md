---
title: Agent issues no queries of its own; sensors are pure event listeners
tags: [architecture, query-audit]
status: verified 2026-10-20 auto
source: [docs/sessions/status/ryan.md]
as_of: 6866d739e 2026-10-12
---
The agent issues no queries of its own. Sensors are pure event listeners. Nuance: `auth()->user()` on public routes for logged-in visitors can trigger the framework guard's lazy `retrieveById()` SELECT. Audit confirmed 2026-10-09.

**Why:** Query noise in high-volume apps would skew collection; pure event listening keeps the agent invisible to query stats.

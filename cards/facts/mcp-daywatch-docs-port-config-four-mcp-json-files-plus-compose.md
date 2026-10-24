---
title: daywatch-docs port 8091 is set in four .mcp.json files plus the daywatch-mcp compose file
tags: [mcp, ops, config]
status: verified 2026-10-24 auto
source: [docs/sessions/ryan/2026-10-08-mcp-docs-server-restart.md]
as_of: fb77aaeac 2026-10-24
---
The `daywatch-docs` server listens on `http://localhost:8091/mcp`, configured identically in all four sibling repos' `.mcp.json`. Changing the port requires editing those four `.mcp.json` files together with the `daywatch-mcp` compose file — the doc-roots contract (see daywatch-mcp AGENTS.md).

**Why:** The port is only configurable by editing both sides at once, so a change on one side alone leaves it unchanged. If another local app legitimately wants 8091, the clash must be resolved on both the `.mcp.json` and compose sides together.

---
title: daywatch-docs MCP server listens on http://localhost:8091/mcp
tags: [mcp, ops, config]
status: verified 2026-10-24 auto
source: [docs/sessions/ryan/2026-10-08-mcp-docs-server-restart.md]
as_of: fb77aaeac 2026-10-24
---
The `daywatch-docs` MCP server listens on `http://localhost:8091/mcp`.

**Why:** When nothing listens on 8091 the MCP client panel shows `daywatch failed http 0 tools` — the client is POSTing JSON-RPC to a dead app, or to a foreign app (Ryan saw a Laravel-style `{"message": "Server Error"}` from a stray dev server) with no `/mcp` route.

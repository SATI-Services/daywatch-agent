---
title: daywatch-docs MCP server exposes three tools: list_docs, read_doc, search_docs
tags: [mcp, tools]
status: verified 2026-10-24 auto
source: [docs/sessions/ryan/2026-10-08-mcp-docs-server-restart.md]
as_of: 12a577fbe 2026-10-24
---
The `daywatch-docs` MCP server exposes three tools: `list_docs`, `read_doc`, `search_docs`. After the restart, `tools/list` returned all three over the MCP handshake.

**Why:** The panel's "0 tools" reading is a symptom of the server being down, not of a broken or reduced tool list — the symptom was gone server-side after the restart.

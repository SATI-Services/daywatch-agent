---
title: Reconnect/restart the MCP client to pick the daywatch-docs server back up
tags: [mcp, ops]
status: open
owner: ryan
since: 2026-10-08
source: [docs/sessions/ryan/2026-10-08-mcp-docs-server-restart.md]
as_of: 12a577fbe 2026-10-24
---
The `daywatch-docs` server (`http://localhost:8091/mcp`) was restarted on 2026-10-08 after Ryan's client panel showed `failed / 0 tools`, but the MCP client does not pick the server back up on its own — it needs a reconnect/restart.

Done = the client is reconnected/restarted and its panel lists the three tools (`list_docs`, `read_doc`, `search_docs`) instead of "0 tools".

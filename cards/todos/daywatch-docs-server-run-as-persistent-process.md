---
title: Run the daywatch-docs MCP server as a persistent process
tags: [mcp, ops]
status: open
owner: ryan
since: 2026-10-08
source: [docs/sessions/ryan/2026-10-08-mcp-docs-server-restart.md]
as_of: 12a577fbe 2026-10-24
---
The 2026-10-08 restart (`node dist/index.js --http 8091` from the `daywatch-mcp` checkout) is tied to the agent session that started it as a background task and dies when that session ends.

For a persistent server: `docker compose up -d --build` in `daywatch-mcp` (has `restart: unless-stopped` + healthcheck), or a detached host `node` process. Left to Ryan which he prefers — no repo change needed either way.

Done = the server survives the session ending and `GET /health` on port 8091 returns 200.

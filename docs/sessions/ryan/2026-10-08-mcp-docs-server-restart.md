# 2026-10-08 — `daywatch-docs` MCP server was down (diagnosed + restarted)

**Author:** sts-ryan-holton
**Agent:** kimi (unreported)

## What happened

Ryan's MCP client panel showed:

```
daywatch   failed   http   0 tools
error: Streamable HTTP error: Error POSTing to endpoint: { "message": "Server Error" }
```

That's the `daywatch-docs` server (`http://localhost:8091/mcp`, configured
identically in all four sibling repos' `.mcp.json`) failing its handshake.

## Diagnosis

- **Nothing was listening on port 8091** — `lsof -nP -iTCP:8091 -sTCP:LISTEN`
  empty, curl connection-refused. No `daywatch-mcp` Docker container exists
  (`docker compose ps` in daywatch-mcp shows none; `docker ps -a` has no
  daywatch images running), and no host node process either.
- The `{"message": "Server Error"}` body in the error is a **Laravel-style
  500**, not something the Node docs server emits — so at the moment Ryan saw
  the error, *something else* was bound to 8091 (a stray dev server, since
  gone) and the client was POSTing JSON-RPC to an app with no `/mcp` route.
  Either way, the real docs server was down; that was the whole problem.
- No MCP server named exactly "daywatch" over HTTP exists in user-level
  configs (`~/.claude.json`, VS Code, etc.) — the panel entry is the
  repo `.mcp.json` `daywatch-docs` server.

## Fix

Started the server from the `daywatch-mcp` checkout (`dist/` was already
built, repo clean on `main`):

```sh
node dist/index.js --http 8091
```

Verified end-to-end with curl:

- `GET /health` → 200, `{"status":"ok", … "projects":["system","daywatch","daywatch-agent","daywatch-ingest"],"docs":75}`
- MCP `initialize` handshake → 200 with capabilities
- `tools/list` → all three tools (`list_docs`, `read_doc`, `search_docs`) —
  the "0 tools" symptom is gone server-side.

## Caveats / follow-ups

- **The restarted process is tied to this agent session** (background task) and
  dies when it ends. For a persistent server: `docker compose up -d --build`
  in `daywatch-mcp` (has `restart: unless-stopped` + healthcheck), or a
  detached host `node` process. Left to Ryan which he prefers — no repo change
  needed either way.
- The MCP client needs a reconnect/restart to pick the server back up.
- If another local app legitimately wants port 8091, there's a port clash to
  resolve (the docs server's port is only configurable by editing the four
  `.mcp.json` files + compose together — doc-roots contract, see daywatch-mcp
  AGENTS.md).

No code changed; no tests run (nothing to test — pure ops diagnosis).

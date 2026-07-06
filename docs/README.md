# daywatch-agent docs

Package-local documentation for `daywatch/agent`. When this package sits in
the Daywatch monorepo, the `daywatch-mcp` docs server auto-detects this
directory and serves it as the `daywatch-agent` project (corpus paths
`daywatch-agent/<file>`).

## What lives here vs the system corpus

**Here (package-local):** only docs specific to this package's own
implementation. The engineering guide is `../CLAUDE.md` (mirrored verbatim by
`../AGENTS.md`); consumer-facing usage guidance ships as Laravel Boost
guidelines under `../resources/boost/guidelines/`. Nothing in this directory
is wire contract.

**System corpus (deliberately NOT here):** the shared Daywatch design docs,
owned by the `daywatch-mcp` package (`services/daywatch-mcp/docs/` in the
monorepo) and served by its MCP server as the `system` project:

| Corpus path (via MCP) | What it is for this package | Monorepo path |
|---|---|---|
| `system/agent-protocol.md` | **The contract this package implements** — record fields, `_group` recipes, byte caps, TCP framing, HTTP ingest protocol, config option table (§7) | `services/daywatch-mcp/docs/agent-protocol.md` |
| `system/samples/records.v1.json` | Canonical sample batch — one record of every v1 type; payload tests and smoke tests reuse it | `services/daywatch-mcp/docs/samples/records.v1.json` |
| `system/research/nightwatch-internals.md` | Nightwatch package/agent teardown — the reference when the protocol doc doesn't settle an implementation detail | `services/daywatch-mcp/docs/research/nightwatch-internals.md` |
| `system/research/nightwatch-agent-internals.md` | Source-verified Nightwatch protocol spec (framing, auth flow, all 13 record types, config surface) | `services/daywatch-mcp/docs/research/nightwatch-agent-internals.md` |
| `system/research/nightwatch-wire-protocol-parity.md` | Nightwatch-parity reference: fields/types v1 deliberately trims (M4+/backlog) | `services/daywatch-mcp/docs/research/nightwatch-wire-protocol-parity.md` |
| `system/architecture.md`, `system/decisions.md`, `system/roadmap.md` | System design, load-bearing decisions, milestones | `services/daywatch-mcp/docs/…` |

Contract changes are **docs-first** — the spec in the system corpus changes
before any code here does. Procedure: `../.claude/skills/daywatch-payloads/`.

## Connecting to the docs server

The server exposes `list_docs`, `read_doc(path)`, and
`search_docs(query, project?)`; corpus paths are `<project>/<relative>`, e.g.
`read_doc("system/agent-protocol.md")`.

### stdio (local development)

Build the server once in your `daywatch-mcp` checkout
(`../../services/daywatch-mcp` from this package in the monorepo):

```sh
cd ../../services/daywatch-mcp
npm ci && npm run build
```

Then in this package's `.mcp.json`:

```json
{
  "mcpServers": {
    "daywatch-docs": {
      "command": "node",
      "args": ["/absolute/path/to/services/daywatch-mcp/dist/index.js"]
    }
  }
}
```

Use the absolute path to `dist/index.js` in your checkout. Run from inside
the monorepo, the server auto-detects the sibling docs roots (including this
`docs/` directory as `daywatch-agent`); standalone it serves just its own
`system` docs — missing roots are skipped silently.

### Docker / shared server (streamable HTTP)

```sh
cd ../../services/daywatch-mcp   # or wherever daywatch-mcp is checked out
docker compose up -d --build     # serves POST /mcp on :8091, plus GET /health
curl http://localhost:8091/health
```

```json
{
  "mcpServers": {
    "daywatch-docs": {
      "type": "http",
      "url": "http://localhost:8091/mcp"
    }
  }
}
```

Swap `localhost` for your shared docs host after the repo split — the HTTP
mode is how split repos keep reading one canonical corpus. The server is
read-only and unauthenticated by design; keep it on a private network.

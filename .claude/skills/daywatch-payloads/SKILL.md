---
name: daywatch-payloads
description: Change procedure for the Daywatch telemetry record contract, scoped to the agent package (producer side) — record DTO + sensor + tests. Use whenever adding, renaming, or removing a record field or record type, or changing framing/HTTP protocol.
---

<!--
  Package-scoped copy of the monorepo root `.claude/skills/daywatch-payloads/`
  skill: identical contract rules, but the checklist covers only this
  package's steps. Canonical for this repo after the repo split — until then,
  keep the shared rules in sync with the root original.
-->

# Changing the record contract (agent-package view)

The single source of truth for record envelopes, per-type fields, `_group`
recipes, byte caps, TCP framing, and the ingest HTTP contract is
**`agent-protocol.md` in the system docs corpus**. Read it as:

- `read_doc("system/agent-protocol.md")` on the `daywatch-docs` MCP server
  (connection snippet in `docs/README.md`), or
- `../../services/daywatch-mcp/docs/agent-protocol.md` when this package sits
  inside the monorepo.

Three components implement the contract independently — this package
(producer), the ingest API (consumer/transformer, `apps/daywatch` or the
`services/daywatch-ingest` relay), and the ClickHouse schema (storage,
`daywatch/data-model.md` §9 row mapping) — so **the doc changes first, then
the components fan out**. Never change a payload this package emits without
the spec change landing first.

## Compatibility rules

- Every record carries a per-type schema version `v`. **Additive** optional
  fields: keep `v`, server treats them as absent-tolerant. **Renames,
  removals, or semantic changes: bump that type's `v`.**
- The server must accept both `v` and `v-1` for at least one release cycle
  (monitored apps upgrade the package slowly). Unknown `t` or `v`: count and
  drop with a metric — never reject the batch (never punish new agents
  talking to old servers, or vice versa).
- Field names, `_group` hash inputs, cap tiers (255 B / 64 KB / 16 MB, byte
  truncation), and integer-µs durations are wire contract — changing any of
  them is a versioned change, not a refactor.
- Frame version (`v1` in `{len}:v1:{token_hash}:{payload}`) only changes for
  framing/transport changes, not record changes. A daemon receiving an
  unknown frame version does a final digest and exits 0.

## Package checklist for any contract change

1. **Spec first (lives outside this package):** the change lands in the
   system docs corpus — `agent-protocol.md` (field table, `_group` recipe,
   `v` bump if needed) and the canonical sample batch
   `system/samples/records.v1.json` — before you write code. In the monorepo
   that's `services/daywatch-mcp/docs/`; from a split checkout, raise the
   spec change against that repo first.
2. **Record DTO** (`src/Records/`): the field, its byte-cap tier, the
   `_group` hash input, and the `v` bump if the change is non-additive.
3. **Sensor** (`src/Sensors/`): populate the field; add a config option in
   `config/daywatch.php` if the field is gated (the option table is
   agent-protocol.md §7 — document it there in step 1).
4. **Tests:** sensor test asserting the exact new record array (field names
   are wire contract); reuse the updated canonical sample batch where tests
   consume one; hostile-host coverage if the change adds a failure path.
5. **Downstream fan-out is not this package's job** — ingest, schema,
   dashboard, and demo app are updated in parallel by their owning agents
   once the spec is merged. Just never ship an emit-side change ahead of the
   spec.

## Quick reference (stable — details in `system/agent-protocol.md`)

- Envelope: `v`, `t`, `timestamp` (float unix s), `deploy`, `server` + usually
  `_group`, `trace_id`, `execution_id`, `execution_source`,
  `execution_preview`, `execution_stage`, `user`.
- Types v1: `request`, `query`, `exception`, `queued-job`, `job-attempt`,
  `command`, `scheduled-task`, `outgoing-request`, `cache-event`, `log`.
- HTTP: gzip `{"records":[…]}` → `POST /api/ingest`, `Bearer dw_…`,
  `Daywatch-Batch-Id` UUID (idempotency, reused on retries). Responses:
  202 / 202-duplicate / 401 / 413 / 422 / 429 `{retry_in}` / 503
  `{stop, refresh_in}`.
- Canonical sample batch (one record of every v1 type, valid `{"records":[…]}`
  body): `system/samples/records.v1.json` (monorepo:
  `../../services/daywatch-mcp/docs/samples/records.v1.json`) — updated in
  step 1 of any contract change; package tests and curl smoke tests reuse it.
- Full Nightwatch-parity reference (deferred fields/types, agent-auth
  exchange): `system/research/nightwatch-wire-protocol-parity.md`.

# daywatch-agent — the collector package

The composer package (`daywatch/agent`) installed into monitored Laravel apps:
sensors observe framework events into a bounded buffer, executions digest the
buffer over framed TCP to the local `php artisan daywatch:agent` daemon
(ReactPHP), which batches, gzips, and POSTs to the central `/api/ingest`.
This mirrors `laravel/nightwatch`'s proven internals — the full teardown is
served by the `daywatch-docs` MCP server as
`system/research/nightwatch-internals.md` (sibling checkout:
`../daywatch-mcp/docs/research/nightwatch-internals.md`); the
contract this package implements is `system/agent-protocol.md` (sibling
checkout: `../daywatch-mcp/docs/agent-protocol.md`) — read it before
touching anything here. The `daywatch-docs` MCP server (configured in
`.mcp.json`) serves the full corpus — use `list_docs`/`read_doc`/`search_docs`;
the sibling `../daywatch-mcp/docs/` checkout is the same content on disk.

This repo is one of four sibling checkouts inside the `daywatch-project`
parent folder — `../daywatch` (central Laravel app), `../daywatch-ingest`
(Java relay), `../daywatch-mcp` (docs server + system corpus). There is no
shared parent-level `CLAUDE.md`; this file is the engineering guide,
mirrored verbatim across `CLAUDE.md`/`AGENTS.md` (`CLAUDE.md` is the editing
source — re-copy to `AGENTS.md` after edits). The package-local
`.claude/agents/agent-package-engineer.md` and
`.claude/skills/daywatch-payloads/` are canonical in this repo.

## Sibling components (linked, but this package runs alone)

- The daemon POSTs to **either** ingest implementation — the Laravel app
  (`../daywatch`, default + reference, `POST /api/ingest`) or the Java relay
  (`../daywatch-ingest`, high-throughput alternative) — selected purely
  by `DAYWATCH_BASE_URL`. The package never knows or cares which; the only
  coupling is the wire contract (`../daywatch-mcp/docs/agent-protocol.md`), changed docs-first
  via the `daywatch-payloads` skill.
- **Independent development:** `composer install && composer test` needs no
  sibling service — no ClickHouse, Redis, MySQL, or running server. The
  "hostile host" suite explicitly proves the package works (silently) with no
  server at all. To exercise it against a real stack, symlink it into a host
  app (composer path repository pointing at this checkout) and point it at a
  running ingest.

## The cardinal rule

**This package must be un-crashable.** Every event hook, sensor call, socket
write, and encode is wrapped in try/catch and failures are swallowed (optionally
error-logged). `register()` stashes boot exceptions and reports them in
`boot()`. Worst case with a dead daemon: ~1 s per digest (0.5 s connect + 0.5 s
write timeout), zero user-visible exceptions. Any code path that can throw into
the host app is a P0 bug.

## Status & constraints

Scaffolded + M2 **complete** (sensors, records, socket client, ReactPHP daemon +
stats/STATS surface; all 14 sensors; 300 tests, non-flaky; `composer test` green — CI matrix
runs the suite green on Laravel 11/12/13). Pinned
constraints (don't drift): `illuminate/support ^11|^12|^13`, PHP `^8.2`; daemon
deps are tilde-to-locked `react/{event-loop ~1.6.0, socket ~1.17.0, promise
~3.3.0, stream ~1.4.0}` — the outbound POST is raw HTTP/1.1 over `react/socket`,
NOT `react/http` (it pins `psr/http-message ^1.0`, incompatible with a Laravel 13
host's `^2.0`); plain deps, no phar/scoper in v1. Keep the package
**headless**: no Livewire/Tailwind/UI dependencies ever.

**Dependency policy:** the `require` block stays permissive **only** for the two
constraints that ARE the package's host-compatibility contract — illuminate
`^11|^12|^13` and php `^8.2`; never tighten those for tooling's sake. Everything
else in `require` is deliberately locked: the `react/*` daemon deps are pinned
tilde-to-lock to their installed minors (patch drift only), matching the
`require-dev` idiom. **`ext-zlib` is a `suggest`, not a `require`** — zlib is
near-universal but not guaranteed, so a host missing it still installs and runs
(sensors/buffer/socket never touch zlib); only the daemon's gzip upload is
affected, and `IngestDispatcher` guards `gzencode` with `function_exists` so a
missing extension pauses upload (drop + log) instead of fatalling. The gzip wire
format itself is unchanged — dropping compression would be a cross-repo contract
change (docs-first via the `daywatch-payloads` skill), not a package-local one.
`require-dev` is pinned
tilde-to-lock for reproducible dev installs (testbench `~11.1.0`, pest `~4.7.4`,
pest-plugin-laravel `~4.1.0`, pint `~1.29.3`); the CI matrix swaps the testbench
constraint per Laravel line (`^9.2`/`^10`/`^11`) before `composer update`, so
the pin only governs local dev. Note dev tooling needs PHP ≥ 8.3 (every Pest 4.x
does) while consumers keep `^8.2` — dev deps never ship to hosts.

## Package shape (M2, built)

```
src/
  AgentServiceProvider.php   # plain Illuminate ServiceProvider; register-only wiring + `about` section
  Core.php                   # per-execution state: trace/execution ids, stage, sampling decision
  SensorManager.php
  Buffer/RecordsBuffer.php   # TWO bounds: ≤500 records AND ≤buffer_bytes; auto-digest when full; ring-drop if disabled
  Sensors/                   # 14 sensors: Request, Query, Exception, Cache, Command, JobAttempt, Log, Mail, Notification, OutgoingRequest, QueuedJob, ScheduledTask, Stage, User
  Records/Envelope.php       # THE shared wire mapping: child/execution/minimal head shapes (+ Counters::tail)
  Records/                   # one DTO per record type, envelope + own fields only; _group hashing; byte-cap truncation
  Ingest/SocketClient.php    # stream_socket_client, 0.5s timeouts, {len}:v1:{hash}:{json} frame, 2:OK ack, stats()
  Console/AgentCommand.php   # daywatch:agent — ReactPHP TCP server + StreamBuffer + gzip POST + DaemonStats/StatsReporter; live TTY dashboard (ConsoleDashboard + RecentLog) + resilient loop; boot AuthProbe + actionable EADDRINUSE report
  Console/StatusCommand.php  # daywatch:status — STATS counters (table / --json), PING fallback, exit 1 when down
  Facades/Daywatch.php       # user(), sample(), dontSample(), report(), ignore(), pause(), resume(), digest()
config/daywatch.php          # full option table in agent-protocol.md §7 (daywatch-mcp system docs)
```

## Non-negotiable behaviors (from the protocol doc)

- Head sampling decided once per execution; sensors always buffer — the
  decision picks digest vs flush at the end. `report()` re-rolls with
  `sampling.exceptions` so errors escape sampled-out traces.
- Trace/sample/user propagate to queue hops via hidden Context keys
  (`daywatch_trace_id`, `daywatch_should_sample`, `daywatch_user_id`).
- String caps are **byte** truncation (255 B / 64 KB / 16 MB tiers) applied
  before buffering; durations are integer **microseconds**; redaction happens
  in-app (the privacy boundary), never downstream.
- **All shared wire fields live in `Records/Envelope.php`** — three head shapes
  (`child(t,group)` for records emitted *during* an execution, `execution(t,group)`
  for records that *are* one, `minimal(t)` for `user`) plus `Counters::tail()` for
  the twelve counters + `peak_memory_usage`/`exception_preview`/`context` that every
  execution-root record ends with. A record DTO declares `Envelope $envelope` and
  emits **only its own fields**; sensors build one with `Envelope::for($core)`.
  Adding a record type = pick a head shape, append your fields, done — never
  re-inline the envelope (`EnvelopeTest` fails the build if a DTO does).
- The in-process buffer is bounded by **records AND bytes**
  (`ingest.event_buffer` / `ingest.buffer_bytes`): field caps let one record carry
  megabytes, so a count alone is not a memory bound on the host.
- **User resolution is memoised + re-entrancy latched.** `Daywatch::user()` treats a
  string/int as an id even when it happens to name a global function
  (`is_callable('info')` is true — honouring it would invoke host code), and a
  resolver that logs or queries can't recurse back through a sensor.
- The daemon never re-parses record JSON — string-level buffer concatenation;
  flush ≥ 6 MB or 10 s; ≤ 5 in-flight POSTs; retry ladder + 503 `stop`
  NullBuffer pause contract per `../daywatch-mcp/docs/agent-protocol.md` §6.
- Worker/Octane state resets between executions (fresh ids, counters,
  `memory_reset_peak_usage()`); worker-loop noise is never sampled.

## Daemon observability (STATS / stats log)

Decision recorded here (2026-07) because `../daywatch-mcp/docs/decisions.md`
is owned by concurrent agents: the local framing gained an **additive `STATS` payload
literal** (frame version stays `v1`, documented like `PING` in
`../daywatch-mcp/docs/agent-protocol.md` §4) so operators can see the daemon is alive and moving
records. The daemon keeps O(1) counters (`Daemon/DaemonStats`): records
received/buffered, buffered bytes, batches/records sent, send failures, retries,
last flush time/size, plus the configured base URL — reported verbatim since the
daemon can't know whether the Laravel app or the Java relay answers it. Records
are counted ONCE per digest at the frame boundary (`Daemon/RecordCounter`, a
C-speed string scan honouring JSON strings/escapes — record JSON is still never
re-parsed). Surfaces: a one-line stats log on the daemon's stdout every
`daywatch.daemon.stats_interval` s (env `DAYWATCH_DAEMON_STATS_INTERVAL`,
default 60, `0` disables; `Daemon/StatsReporter`, guarded so a logging failure
can never crash the loop), and `daywatch:status` (STATS frame → ack +
`{len}:{json}` reply, token-gated; human table or `--json`; daemon down = clear
"daemon unreachable" message + exit 1, never an exception).

When `daywatch:agent` runs attached to a **TTY** it renders a live in-place
dashboard instead of scrolling lines (`Daemon/ConsoleDashboard`, self-rescheduling
+ guarded like `StatsReporter`): the startup auth check, memory/uptime, ingest
throughput, auth errors (401s tracked via `DaemonStats::authFailed()`, kept off
the frozen STATS `toArray()` contract), and the last N log lines — the daemon's logger feeds a
bounded `Daemon/RecentLog` ring buffer in this mode. Repaints every
`daywatch.daemon.console_refresh` s (env `DAYWATCH_DAEMON_CONSOLE_REFRESH`,
default 3, `0` disables), keeping `daywatch.daemon.console_lines`
(`DAYWATCH_DAEMON_CONSOLE_LINES`, default 10) lines. `--plain`, a non-TTY
(supervisor/log file), or refresh 0 falls back to plain-line logging + the
periodic stats line. Resilience: `AgentCommand::runResiliently()` re-enters the
event loop if any callback ever lets an exception escape `Loop::run()` (bounded
against a hot re-throw loop), so the daemon never dies on an internal error —
only a deliberate shutdown frame stops it.

## Startup authentication check (`Daemon/AuthProbe`)

`daywatch:agent` answers "is this agent actually authenticated?" at boot instead
of leaving it to be inferred from silently dropped batches. `Daemon/AuthProbe`
POSTs an **empty batch** — `{"records":[]}`, gzipped, carrying the same headers a
real flush carries — to `{base_url}/api/ingest`. That is the one probe **both**
ingest implementations answer identically and conclusively: auth precedes all
payload handling in each, and an empty records array is a valid batch that stores
nothing (Laravel app → `202 {"accepted":0}` with zero rows RPUSHed; Java relay →
same, zero rows enqueued). So 2xx = token accepted, 401/403 = rejected, 404 =
`DAYWATCH_BASE_URL` points at no ingest, 429/503/5xx = up but shedding (token
unproven — the relay also 429s when its own tenancy directory is down), transport
rejection = unreachable. On success only, a best-effort `GET
{base_url}/api/ingest/tenancy` names the resolved project/environment; that
endpoint exists on the Laravel app (it backs the relay's token lookup) and NOT on
the relay, so its 404 never downgrades the headline result.

Diagnostic only: the promise never rejects, the check never blocks the loop, and a
failure never stops the daemon (the ingest may just not be up yet — the dispatcher
owns retries). Surfaces: one boot log line (`[daywatch:agent] auth …`) and the
dashboard's `auth` row. Gated by `daywatch.daemon.auth_check` (env
`DAYWATCH_DAEMON_AUTH_CHECK`, default true) / `--no-auth-check`. The outcome lives
on `DaemonStats::authProbed()/authState()/authSummary()` but is deliberately kept
**off `toArray()`** — like `authFailures()`, that array is the frozen STATS wire
contract, so exposing the auth state to `daywatch:status` would be a docs-first
cross-repo change (`daywatch-payloads` skill), not a package-local one.

The other boot failure worth naming is a taken port: `EADDRINUSE` is caught and
reported as "another daywatch:agent is probably already running", with the
`daywatch:status` / `lsof -nP -iTCP:{port} -sTCP:LISTEN` / `--listen=` next steps,
instead of leaking the raw ReactPHP socket message.

## Ship Laravel Boost guidance for consumer apps

This is a package, not an app — it doesn't install Boost, but it **ships Boost
guidelines** so any host app that runs `boost:install` auto-loads how to use
Daywatch. Add `resources/boost/guidelines/core.blade.php`: publish
`config/daywatch.php`, set `DAYWATCH_TOKEN` / `DAYWATCH_BASE_URL` /
`DAYWATCH_INGEST_URI`, run the `daywatch:agent` daemon, and the
`Daywatch::user()/sample()/report()/ignore()` API. Keep it a
short, example-driven overview per agentskills.io — consumer-facing, distinct from
this dev guide.

## Testing

Pest + Testbench across the Laravel 11/12/13 matrix. Sensor tests assert exact
record payloads (field names are wire contract — see `daywatch-payloads`
skill). Socket client tested against a stub TCP server; daemon batching/retry
tested with ReactPHP's test utilities; a "hostile host" suite proves nothing
throws when the daemon is down, the token is wrong, or payloads are oversized.

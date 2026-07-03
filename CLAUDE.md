# packages/daywatch-agent — the collector package

The composer package (`daywatch/agent`) installed into monitored Laravel apps:
sensors observe framework events into a bounded buffer, executions digest the
buffer over framed TCP to the local `php artisan daywatch:agent` daemon
(ReactPHP), which batches, gzips, and POSTs to the central `/api/ingest`.
This mirrors `laravel/nightwatch`'s proven internals — the full teardown is in
`docs/research/nightwatch-internals.md`; the contract this package implements
is `docs/agent-protocol.md` (read it before touching anything here).

Shared workflows, coding standards, and commit flow live in the repo-root
`CLAUDE.md` (`../../CLAUDE.md`); this file is package-specific.

## Sibling components (linked, but this package runs alone)

- The daemon POSTs to **either** ingest implementation — the Laravel app
  (`apps/daywatch`, default + reference, `POST /api/ingest`) or the Java relay
  (`services/daywatch-ingest`, high-throughput alternative) — selected purely
  by `DAYWATCH_BASE_URL`. The package never knows or cares which; the only
  coupling is the wire contract (`docs/agent-protocol.md`), changed docs-first
  via the `daywatch-payloads` skill.
- **Independent development:** `composer install && composer test` needs no
  monorepo service — no ClickHouse, Redis, MySQL, or running server. The
  "hostile host" suite explicitly proves the package works (silently) with no
  server at all. To exercise it against a real stack, symlink it into a host
  app (see the root-level install notes) and point it at a running ingest.

## The cardinal rule

**This package must be un-crashable.** Every event hook, sensor call, socket
write, and encode is wrapped in try/catch and failures are swallowed (optionally
error-logged). `register()` stashes boot exceptions and reports them in
`boot()`. Worst case with a dead daemon: ~1 s per digest (0.5 s connect + 0.5 s
write timeout), zero user-visible exceptions. Any code path that can throw into
the host app is a P0 bug.

## Status & constraints

Scaffolded + M2 **complete** (sensors, records, socket client, ReactPHP daemon +
stats/STATS surface; 186 tests, non-flaky; `composer test` green). Pinned
constraints (don't drift): `illuminate/support ^11|^12|^13`, PHP `^8.2`,
`ext-zlib`; daemon deps `react/{event-loop ^1.5, socket ^1.16, promise ^3.2,
stream ^1.4}` — the outbound POST is raw HTTP/1.1 over `react/socket`, NOT
`react/http` (it pins `psr/http-message ^1.0`, incompatible with a Laravel 13
host's `^2.0`); plain deps, no phar/scoper in v1. Keep the package
**headless**: no Livewire/Tailwind/UI dependencies ever.

**Dependency policy:** the `require` block stays permissive (illuminate
`^11|^12|^13`, php `^8.2`) — that IS the package's compatibility contract with
host apps; never tighten it for tooling's sake. `require-dev` is pinned
tilde-to-lock for reproducible dev installs (testbench `~11.1.0`, pest `~4.7.4`,
pest-plugin-laravel `~4.1.0`, pint `~1.29.3`); the CI matrix swaps the testbench
constraint per Laravel line (`^9.2`/`^10`/`^11`) before `composer update`, so
the pin only governs local dev. Note dev tooling needs PHP ≥ 8.3 (every Pest 4.x
does) while consumers keep `^8.2` — dev deps never ship to hosts.

## Package shape (M2, built)

```
src/
  AgentServiceProvider.php   # spatie PackageServiceProvider; register-only wiring
  Core.php                   # per-execution state: trace/execution ids, stage, sampling decision
  SensorManager.php
  Buffer/RecordsBuffer.php   # ≤500 records; auto-digest when full; ring-drop if disabled
  Sensors/                   # RequestSensor, QuerySensor, ExceptionSensor (M2); more in M4
  Records/                   # one DTO per record type; _group hashing; byte-cap truncation
  Ingest/SocketClient.php    # stream_socket_client, 0.5s timeouts, {len}:v1:{hash}:{json} frame, 2:OK ack, stats()
  Console/AgentCommand.php   # daywatch:agent — ReactPHP TCP server + StreamBuffer + gzip POST + DaemonStats/StatsReporter
  Console/StatusCommand.php  # daywatch:status — STATS counters (table / --json), PING fallback, exit 1 when down
  Console/DeployCommand.php  # daywatch:deploy — POST /api/deploys
  Facades/Daywatch.php       # user(), sample(), dontSample(), report(), ignore(), pause(), resume(), digest()
config/daywatch.php          # full option table in docs/agent-protocol.md §7
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
- The daemon never re-parses record JSON — string-level buffer concatenation;
  flush ≥ 6 MB or 10 s; ≤ 5 in-flight POSTs; retry ladder + 503 `stop`
  NullBuffer pause contract per `docs/agent-protocol.md` §6.
- Worker/Octane state resets between executions (fresh ids, counters,
  `memory_reset_peak_usage()`); worker-loop noise is never sampled.

## Daemon observability (STATS / stats log)

Decision recorded here (2026-07) because `docs/decisions.md` is owned by
concurrent agents: the local framing gained an **additive `STATS` payload
literal** (frame version stays `v1`, documented like `PING` in
`docs/agent-protocol.md` §4) so operators can see the daemon is alive and moving
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

## Ship Laravel Boost guidance for consumer apps

This is a package, not an app — it doesn't install Boost, but it **ships Boost
guidelines** so any host app that runs `boost:install` auto-loads how to use
Daywatch. Add `resources/boost/guidelines/core.blade.php`: publish
`config/daywatch.php`, set `DAYWATCH_TOKEN` / `DAYWATCH_BASE_URL` /
`DAYWATCH_INGEST_URI`, run the `daywatch:agent` daemon + `daywatch:deploy` on
release, and the `Daywatch::user()/sample()/report()/ignore()` API. Keep it a
short, example-driven overview per agentskills.io — consumer-facing, distinct from
this dev guide.

## Testing

Pest + Testbench across the Laravel 11/12/13 matrix. Sensor tests assert exact
record payloads (field names are wire contract — see `daywatch-payloads`
skill). Socket client tested against a stub TCP server; daemon batching/retry
tested with ReactPHP's test utilities; a "hostile host" suite proves nothing
throws when the daemon is down, the token is wrong, or payloads are oversized.

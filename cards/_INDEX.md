# Index (generated — do not edit; exported from the AID board)

## Facts — 37

Status: verified 37.

Tags: api 1, architecture 4, auth 4, auto-wiring 2, batching 1, buffer 2, cardinal-rule 1, cli 3, command 1, config 1, configuration 3, conflicts 1, constraints 3, context 1, cross-repo 1, daemon 12, dependencies 5, dev-tooling 1, diagnostics 1, env 2, envelope 3, error-handling 2, events 1, facade 1, framing 1, host-compatibility 1, ingest 2, installation 2, laravel 3, legal 1, license 1, limits 1, monitoring 1, observability 4, observation 1, octane 1, performance 1, php 1, pinning 1, propagation 1, protocol 8, queue 1, react 2, reactor 1, records 2, reentrance 1, reliability 1, requirements 1, resilience 3, resolution 1, runtime 1, safety 1, sampling 3, scope 1, secrets 1, sensors 3, sizing 1, socket 1, startup 3, state-reset 1, stats 2, telemetry 2, testing 2, truncation 1, user 1, wire-contract 4, worker 1, zlib 1

| Card | Fact | Tags | Status |
|---|---|---|---|
| [14-sensors-buffer-to-bounded-record-and-byte-limits](facts/14-sensors-buffer-to-bounded-record-and-byte-limits.md) | 14 sensors buffer to bounded limits: ≤500 records AND ≤buffer_bytes | `sensors` `buffer` `limits` `architecture` | verified 2026-09-09 auto |
| [auth-check-surfaces-in-boot-log-and-dashboard-auth-row](facts/auth-check-surfaces-in-boot-log-and-dashboard-auth-row.md) | Auth check surfaces in boot log line and dashboard auth row; gated by DAYWATCH_DAEMON_AUTH_CHECK | `daemon` `auth` `observability` `config` | verified 2026-09-09 auto |
| [auth-probe-never-blocks-daemon-never-stops-it](facts/auth-probe-never-blocks-daemon-never-stops-it.md) | AuthProbe promise never rejects; check never blocks loop or stops daemon | `daemon` `auth` `startup` `resilience` | verified 2026-09-09 auto |
| [auth-probe-posts-empty-batch-at-boot](facts/auth-probe-posts-empty-batch-at-boot.md) | AuthProbe POSTs empty batch {records:[]} at boot to verify token acceptance | `daemon` `auth` `startup` `protocol` | verified 2026-09-09 auto |
| [auth-state-kept-off-stats-toarray-contract](facts/auth-state-kept-off-stats-toarray-contract.md) | Auth state kept off STATS toArray() — frozen wire contract | `daemon` `auth` `observability` `protocol` | verified 2026-09-09 auto |
| [configuration-env-vars-daywatch-token-base-url-required](facts/configuration-env-vars-daywatch-token-base-url-required.md) | DAYWATCH_TOKEN and DAYWATCH_BASE_URL are required; without them app runs normally collecting nothing | `configuration` `secrets` `env` | verified 2026-09-09 auto |
| [daemon-deps-tilde-locked-to-installed-minors](facts/daemon-deps-tilde-locked-to-installed-minors.md) | Daemon react dependencies are tilde-locked; patch drift only | `dependencies` `react` `daemon` `pinning` | verified 2026-09-09 auto |
| [daemon-never-reparses-record-json-buffer-concat](facts/daemon-never-reparses-record-json-buffer-concat.md) | Daemon never re-parses record JSON; uses string-level buffer concatenation | `daemon` `performance` `ingest` `batching` | verified 2026-09-09 auto |
| [daemon-observability-via-stats-payload-and-counters](facts/daemon-observability-via-stats-payload-and-counters.md) | Daemon observability via STATS payload literal and O(1) counters in DaemonStats | `daemon` `observability` `stats` `monitoring` | verified 2026-09-09 auto |
| [daemon-reentrant-resilient-loop-on-exception-escape](facts/daemon-reentrant-resilient-loop-on-exception-escape.md) | Daemon re-enters resilient loop if exception escapes Loop::run(); bounded against hot re-throw | `daemon` `resilience` `error-handling` `reactor` | verified 2026-09-09 auto |
| [daemon-stats-surface-via-stdout-log-and-daywatch-status](facts/daemon-stats-surface-via-stdout-log-and-daywatch-status.md) | STATS surfaces via periodic stdout log and daywatch:status command | `daemon` `observability` `stats` `cli` | verified 2026-09-09 auto |
| [daywatch-about-section-contributed-to-php-artisan-about](facts/daywatch-about-section-contributed-to-php-artisan-about.md) | Daywatch contributes a section to php artisan about | `laravel` `observation` `cli` | verified 2026-09-09 auto |
| [daywatch-agent-command-runs-reactphp-tcp-server-batcher-poster](facts/daywatch-agent-command-runs-reactphp-tcp-server-batcher-poster.md) | daywatch:agent command runs ReactPHP TCP server receiving digests, batches and gzips, POSTs to ingest | `daemon` `command` `cli` | verified 2026-09-09 auto |
| [daywatch-facade-provides-user-sample-report-ignore-pause-resume-digest](facts/daywatch-facade-provides-user-sample-report-ignore-pause-resume-digest.md) | Daywatch facade provides user(), sample(), dontSample(), report(), ignore(), pause(), resume(), digest() | `facade` `api` `runtime` | verified 2026-09-09 auto |
| [dev-tooling-requires-php-8-3-consumers-stay-8-2](facts/dev-tooling-requires-php-8-3-consumers-stay-8-2.md) | Dev tooling needs PHP ≥8.3; consumers stay on php ^8.2 | `dependencies` `dev-tooling` `php` `constraints` | verified 2026-09-09 auto |
| [eaddrinuse-caught-and-reported-with-diagnostics](facts/eaddrinuse-caught-and-reported-with-diagnostics.md) | EADDRINUSE caught at boot and reported with diagnostics and next steps | `daemon` `startup` `error-handling` `diagnostics` | verified 2026-09-09 auto |
| [head-sampling-rates-configurable-errors-escape](facts/head-sampling-rates-configurable-errors-escape.md) | Head-sampling rates configurable (default 1.0); errors escape sampled-out traces | `configuration` `sampling` `telemetry` | verified 2026-09-09 auto |
| [ingest-uri-defaults-to-127-0-0-1-2408](facts/ingest-uri-defaults-to-127-0-0-1-2408.md) | DAYWATCH_INGEST_URI defaults to 127.0.0.1:2408; use 0.0.0.0:2408 in Docker | `configuration` `env` `daemon` | verified 2026-09-09 auto |
| [license-is-mit](facts/license-is-mit.md) | License is MIT | `legal` `license` | verified 2026-09-09 auto |
| [package-discovery-auto-registers-service-provider](facts/package-discovery-auto-registers-service-provider.md) | Service provider auto-registers via Laravel package discovery | `laravel` `auto-wiring` `installation` | verified 2026-09-09 auto |
| [package-is-uncrashable](facts/package-is-uncrashable.md) | Package must be uncrashable; all hooks and socket writes are wrapped | `cardinal-rule` `reliability` `architecture` | verified 2026-09-09 auto |
| [package-stays-headless-no-ui-dependencies](facts/package-stays-headless-no-ui-dependencies.md) | Package is headless; no Livewire, Tailwind, or UI dependencies ever | `architecture` `dependencies` `scope` | verified 2026-09-09 auto |
| [php-and-laravel-version-constraints](facts/php-and-laravel-version-constraints.md) | Host-compat constraints are php ^8.2 and illuminate/support ^11|^12|^13 | `constraints` `host-compatibility` `requirements` | verified 2026-09-09 auto |
| [react-http-never-added-psr-http-message-conflict](facts/react-http-never-added-psr-http-message-conflict.md) | Never add react/http; it pins psr/http-message ^1.0 conflicting with Laravel 13 | `dependencies` `react` `conflicts` `constraints` | verified 2026-09-09 auto |
| [record-dtos-emit-only-own-fields-envelope-separate](facts/record-dtos-emit-only-own-fields-envelope-separate.md) | Record DTOs declare Envelope and emit only their own fields; EnvelopeTest enforces | `records` `envelope` `wire-contract` `testing` | verified 2026-09-09 auto |
| [record-envelopes-three-head-shapes-shared-wire-mapping](facts/record-envelopes-three-head-shapes-shared-wire-mapping.md) | Envelopes declare three head shapes: child(t,group) for in-execution, execution(t,group) for executions, minimal(t) for user | `envelope` `records` `wire-contract` `protocol` | verified 2026-09-09 auto |
| [sampling-decided-once-per-execution-errors-escape](facts/sampling-decided-once-per-execution-errors-escape.md) | Head sampling decided once per execution; errors escape sampled-out traces | `sampling` `protocol` `telemetry` | verified 2026-09-09 auto |
| [sensor-manager-14-sensors-cover-framework-events](facts/sensor-manager-14-sensors-cover-framework-events.md) | SensorManager wires 14 sensors covering Request, Query, Exception, Cache, Command, and more | `sensors` `architecture` `events` | verified 2026-09-09 auto |
| [sensor-tests-assert-exact-payload-field-names](facts/sensor-tests-assert-exact-payload-field-names.md) | Sensor tests assert exact payload field names; those names ARE the wire contract | `sensors` `testing` `wire-contract` | verified 2026-09-09 auto |
| [service-provider-and-facade-auto-register-via-discovery](facts/service-provider-and-facade-auto-register-via-discovery.md) | Service provider and Daywatch facade auto-register via package discovery | `installation` `laravel` `auto-wiring` | verified 2026-09-09 auto |
| [socket-client-uses-stream-socket-client-0-5s-timeouts](facts/socket-client-uses-stream-socket-client-0-5s-timeouts.md) | SocketClient uses stream_socket_client with 0.5s timeouts; {len}:v1:{hash}:{json} frame | `socket` `ingest` `protocol` `framing` | verified 2026-09-09 auto |
| [string-caps-are-byte-truncation-before-buffering](facts/string-caps-are-byte-truncation-before-buffering.md) | String caps are byte truncation (255B/64KB/16MB tiers) before buffering | `truncation` `buffer` `protocol` `sizing` | verified 2026-09-09 auto |
| [trace-sample-user-propagate-via-hidden-context-keys](facts/trace-sample-user-propagate-via-hidden-context-keys.md) | Trace/sample/user propagate to queue hops via hidden Context keys | `context` `queue` `propagation` `protocol` | verified 2026-09-09 auto |
| [user-resolution-memoised-reentrance-latched](facts/user-resolution-memoised-reentrance-latched.md) | User resolution is memoised and re-entrancy latched | `user` `resolution` `reentrance` `safety` | verified 2026-09-09 auto |
| [wire-contract-is-docs-first-via-payloads-skill](facts/wire-contract-is-docs-first-via-payloads-skill.md) | Wire contract changes are docs-first via daywatch-payloads skill, never package-local | `wire-contract` `cross-repo` `envelope` `protocol` | verified 2026-09-09 auto |
| [worker-octane-state-resets-between-executions](facts/worker-octane-state-resets-between-executions.md) | Worker/Octane state resets between executions; worker-loop noise never sampled | `worker` `octane` `state-reset` `sampling` | verified 2026-09-09 auto |
| [zlib-is-optional-suggest-not-require](facts/zlib-is-optional-suggest-not-require.md) | ext-zlib is a suggest, not a require; gzencode guarded by function_exists | `dependencies` `zlib` `resilience` | verified 2026-09-09 auto |

## Todos — 0

Status: .

| Card | Work | Owner | Since | Tags | Status |
|---|---|---|---|---|---|

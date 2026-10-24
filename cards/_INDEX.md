# Index (generated — do not edit; exported from the AID board)

## Facts — 26

Status: verified 26.

Tags: architecture 1, auto-wiring 2, boost 1, boot 1, ci 1, cli 1, config 7, configuration 3, context 1, core 1, daemon 1, docs 2, draft-feature 1, emit 1, entry-point 1, env 2, facade 1, filtering 4, fluent 1, gotcha 1, installation 2, job-sensor 1, laravel 4, legal 1, license 1, mcp 2, middleware 2, observation 1, ops 2, propagation 1, query-audit 1, query-sensor 1, queue 1, safety 1, sampling 5, secrets 1, sensor 2, sensor-internal 1, service-provider 1, skill 1, telemetry 1, testing 2, uncrashable 2, unresolved 1, wire-contract 2

| Card | Fact | Tags | Status |
|---|---|---|---|
| [agent-issues-no-queries-of-its-own-sensors-pure-event-listeners](facts/agent-issues-no-queries-of-its-own-sensors-pure-event-listeners.md) | Agent issues no queries of its own; sensors are pure event listeners | `architecture` `query-audit` | verified 2026-10-20 auto |
| [boost-guidelines-sensor-configuration-example](facts/boost-guidelines-sensor-configuration-example.md) | Boost guidelines include per-sensor config examples for enabled, sample, and groups | `docs` `boost` `config` | verified 2026-10-21 auto |
| [ci-matrix-laravel-11-legs-dropped-advisory-blocked](facts/ci-matrix-laravel-11-legs-dropped-advisory-blocked.md) | Laravel 11 legs dropped from CI matrix; advisory-blocked upstream | `ci` `laravel` `unresolved` | verified 2026-09-12 auto *volatile* |
| [configuration-env-vars-daywatch-token-base-url-required](facts/configuration-env-vars-daywatch-token-base-url-required.md) | DAYWATCH_TOKEN and DAYWATCH_BASE_URL are required; without them app runs normally collecting nothing | `configuration` `secrets` `env` | verified 2026-09-09 auto |
| [daywatch-about-section-contributed-to-php-artisan-about](facts/daywatch-about-section-contributed-to-php-artisan-about.md) | Daywatch contributes a section to php artisan about | `laravel` `observation` `cli` | verified 2026-09-09 auto |
| [daywatch-facade-methods-never-throw-all-eight-fluent](facts/daywatch-facade-methods-never-throw-all-eight-fluent.md) | All eight Daywatch facade methods never throw and stay fluent; swallow all Core failures | `facade` `uncrashable` `fluent` | verified 2026-10-22 auto |
| [effective-sample-rate-stamped-at-digest-time-in-core](facts/effective-sample-rate-stamped-at-digest-time-in-core.md) | Core.effectiveSampleRate stamps records at digest time; product of head and per-sensor rates | `sampling` `emit` `core` | verified 2026-10-22 auto |
| [head-sampling-rates-configurable-errors-escape](facts/head-sampling-rates-configurable-errors-escape.md) | Head-sampling rates configurable (default 1.0); errors escape sampled-out traces | `configuration` `sampling` `telemetry` | verified 2026-09-09 auto |
| [http-middleware-capture-request-entry-point-never-blocks](facts/http-middleware-capture-request-entry-point-never-blocks.md) | Http/Middleware/CaptureRequest starts request execution; exceptions never block the request | `middleware` `entry-point` `uncrashable` | verified 2026-10-22 auto |
| [ignore-job-names-filters-queued-and-attempt-sensors](facts/ignore-job-names-filters-queued-and-attempt-sensors.md) | filtering.ignore_job_names (default empty) filters both QueuedJobSensor and JobAttemptSensor | `filtering` `job-sensor` `config` | verified 2026-10-23 auto |
| [ingest-uri-defaults-to-127-0-0-1-2408](facts/ingest-uri-defaults-to-127-0-0-1-2408.md) | DAYWATCH_INGEST_URI defaults to 127.0.0.1:2408; use 0.0.0.0:2408 in Docker | `configuration` `env` `daemon` | verified 2026-09-09 auto |
| [license-is-mit](facts/license-is-mit.md) | License is MIT | `legal` `license` | verified 2026-09-09 auto |
| [mcp-daywatch-docs-port-config-four-mcp-json-files-plus-compose](facts/mcp-daywatch-docs-port-config-four-mcp-json-files-plus-compose.md) | daywatch-docs port 8091 is set in four .mcp.json files plus the daywatch-mcp compose file | `mcp` `ops` `config` | verified 2026-10-24 auto |
| [mcp-daywatch-docs-server-port-8091](facts/mcp-daywatch-docs-server-port-8091.md) | daywatch-docs MCP server listens on http://localhost:8091/mcp | `mcp` `ops` `config` | verified 2026-10-24 auto |
| [package-discovery-auto-registers-service-provider](facts/package-discovery-auto-registers-service-provider.md) | Service provider auto-registers via Laravel package discovery | `laravel` `auto-wiring` `installation` | verified 2026-09-09 auto |
| [patterns-php-extracted-needle-glob-precompile](facts/patterns-php-extracted-needle-glob-precompile.md) | Patterns.php (new) extracts needle/glob precompile shared by all filter sensors | `filtering` `sensor-internal` | verified 2026-10-23 auto |
| [per-sensor-record-sampling-seven-high-volume-child-types](facts/per-sensor-record-sampling-seven-high-volume-child-types.md) | Per-sensor record sampling via `sensors.{class}.sample` gates seven high-volume child-record sensor types | `sampling` `config` | verified 2026-10-21 auto |
| [phpunit-env-pins-forced-against-process-env-leak](facts/phpunit-env-pins-forced-against-process-env-leak.md) | phpunit.xml APP_ENV, DB_CONNECTION, DB_DATABASE, DB_URL pins forced to prevent process-env override | `testing` `safety` `config` | verified 2026-10-23 auto |
| [propagation-hidden-context-key-daywatch-sample-rate-for-queue-hops](facts/propagation-hidden-context-key-daywatch-sample-rate-for-queue-hops.md) | Queue hops adopt sample_rate via hidden daywatch_sample_rate Context key, read validates (0,1] | `sampling` `propagation` `context` | verified 2026-10-22 auto |
| [queryexecuted-filter-ignore-queries-and-ignore-query-patterns](facts/queryexecuted-filter-ignore-queries-and-ignore-query-patterns.md) | QuerySensor filters via ignore_queries (drop stream) and ignore_query_patterns (default framework tables) | `filtering` `query-sensor` `config` | verified 2026-10-23 auto |
| [queryexecuted-unconditionally-recorded-ignore-queries-reserved](facts/queryexecuted-unconditionally-recorded-ignore-queries-reserved.md) | QuerySensor records every QueryExecuted; ignore_queries config is reserved, not wired | `sensor` `filtering` `queue` `draft-feature` | verified 2026-10-23 auto |
| [sample-rate-wire-stamping-effective-inclusion-probability](facts/sample-rate-wire-stamping-effective-inclusion-probability.md) | Every digested record carries sample_rate stamping its effective inclusion probability | `sampling` `wire-contract` | verified 2026-10-20 auto |
| [sensor-singletons-resolved-once-at-boot-config-set-has-no-effect](facts/sensor-singletons-resolved-once-at-boot-config-set-has-no-effect.md) | Sensor singletons resolved once at boot; runtime config()->set() has no effect | `sensor` `testing` `gotcha` | verified 2026-10-23 auto |
| [service-provider-and-facade-auto-register-via-discovery](facts/service-provider-and-facade-auto-register-via-discovery.md) | Service provider and Daywatch facade auto-register via package discovery | `installation` `laravel` `auto-wiring` | verified 2026-09-09 auto |
| [service-provider-prepends-capture-request-middleware](facts/service-provider-prepends-capture-request-middleware.md) | Service provider prependMiddleware(CaptureRequest::class) unless RequestSensor is disabled | `middleware` `boot` `service-provider` | verified 2026-10-22 auto |
| [wire-contract-is-docs-first-via-payloads-skill](facts/wire-contract-is-docs-first-via-payloads-skill.md) | Wire contract changes are docs-first: the spec lands in the system docs corpus before any emit-side code | `wire-contract` `docs` `skill` | verified 2026-09-12 auto |

## Todos — 10

Status: open 10.

| Card | Work | Owner | Since | Tags | Status |
|---|---|---|---|---|---|
| [boost-guidelines-published-to-host-apps](todos/boost-guidelines-published-to-host-apps.md) | Add and ship Boost guidelines to resources/boost/guidelines/core.blade.php | unassigned | 2026-09-09 | `laravel` `boost` `documentation` `installation` | open |
| [ci-matrix-laravel-version-constraint-alignment](todos/ci-matrix-laravel-version-constraint-alignment.md) | Align composer.json Laravel constraint with CI testability | ryan | 2026-09-06 | `ci` `composer` `owner-decision` | open |
| [consider-groups-for-outgoing-request-urls](todos/consider-groups-for-outgoing-request-urls.md) | Consider `groups` for other high-cardinality values — outgoing-request URLs | unassigned | 2026-10-12 | `sampling` `feature` | open |
| [daemon-signal-handling-implementation](todos/daemon-signal-handling-implementation.md) | Decide and implement process signal handling (SIGINT/SIGTERM) for daywatch:agent | unassigned | 2026-10-21 | `daemon` `signals` `robustness` | open |
| [daywatch-docs-server-run-as-persistent-process](todos/daywatch-docs-server-run-as-persistent-process.md) | Run the daywatch-docs MCP server as a persistent process | ryan | 2026-10-08 | `mcp` `ops` | open |
| [docs-corpus-agent-protocol-option-table-refresh](todos/docs-corpus-agent-protocol-option-table-refresh.md) | Refresh docs corpus agent-protocol.md §7 option table with new sensors block and per-sensor sample | ryan | 2026-10-12 | `docs` `corpus` | open |
| [legacy-host-app-filtering-config-cleanup](todos/legacy-host-app-filtering-config-cleanup.md) | Delete filtering block from published config when host apps upgrade past 1.0.x | unassigned | 2026-10-12 | `config` `cleanup` | open |
| [mcp-client-reconnect-for-restarted-daywatch-docs-server](todos/mcp-client-reconnect-for-restarted-daywatch-docs-server.md) | Reconnect/restart the MCP client to pick the daywatch-docs server back up | ryan | 2026-10-08 | `mcp` `ops` | open |
| [release-1.3.0-host-apps-upgrade-and-daemon-restart](todos/release-1.3.0-host-apps-upgrade-and-daemon-restart.md) | Upgrade host apps to daywatch/agent 1.3.0 and restart daemons and workers | unassigned | 2026-10-12 | `release` `deploy` | open |
| [wire-ignore-query-patterns-queryexecuted-filter](todos/wire-ignore-query-patterns-queryexecuted-filter.md) | Wire ignore_queries and ignore_query_patterns to filter QueryExecuted events | unassigned | 2026-10-05 | `sensor` `filtering` `queue` | open |

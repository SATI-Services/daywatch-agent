# Index (generated — do not edit; exported from the AID board)

## Facts — 8

Status: verified 8.

Tags: auto-wiring 2, ci 1, cli 1, configuration 3, daemon 1, env 2, installation 2, laravel 4, legal 1, license 1, observation 1, sampling 1, secrets 1, telemetry 1, unresolved 1

| Card | Fact | Tags | Status |
|---|---|---|---|
| [ci-matrix-laravel-11-legs-dropped-advisory-blocked](facts/ci-matrix-laravel-11-legs-dropped-advisory-blocked.md) | Laravel 11 legs dropped from CI matrix; advisory-blocked upstream | `ci` `laravel` `unresolved` | verified 2026-09-12 auto *volatile* |
| [configuration-env-vars-daywatch-token-base-url-required](facts/configuration-env-vars-daywatch-token-base-url-required.md) | DAYWATCH_TOKEN and DAYWATCH_BASE_URL are required; without them app runs normally collecting nothing | `configuration` `secrets` `env` | verified 2026-09-09 auto |
| [daywatch-about-section-contributed-to-php-artisan-about](facts/daywatch-about-section-contributed-to-php-artisan-about.md) | Daywatch contributes a section to php artisan about | `laravel` `observation` `cli` | verified 2026-09-09 auto |
| [head-sampling-rates-configurable-errors-escape](facts/head-sampling-rates-configurable-errors-escape.md) | Head-sampling rates configurable (default 1.0); errors escape sampled-out traces | `configuration` `sampling` `telemetry` | verified 2026-09-09 auto |
| [ingest-uri-defaults-to-127-0-0-1-2408](facts/ingest-uri-defaults-to-127-0-0-1-2408.md) | DAYWATCH_INGEST_URI defaults to 127.0.0.1:2408; use 0.0.0.0:2408 in Docker | `configuration` `env` `daemon` | verified 2026-09-09 auto |
| [license-is-mit](facts/license-is-mit.md) | License is MIT | `legal` `license` | verified 2026-09-09 auto |
| [package-discovery-auto-registers-service-provider](facts/package-discovery-auto-registers-service-provider.md) | Service provider auto-registers via Laravel package discovery | `laravel` `auto-wiring` `installation` | verified 2026-09-09 auto |
| [service-provider-and-facade-auto-register-via-discovery](facts/service-provider-and-facade-auto-register-via-discovery.md) | Service provider and Daywatch facade auto-register via package discovery | `installation` `laravel` `auto-wiring` | verified 2026-09-09 auto |

## Todos — 3

Status: open 3.

| Card | Work | Owner | Since | Tags | Status |
|---|---|---|---|---|---|
| [boost-guidelines-published-to-host-apps](todos/boost-guidelines-published-to-host-apps.md) | Add and ship Boost guidelines to resources/boost/guidelines/core.blade.php | unassigned | 2026-09-09 | `laravel` `boost` `documentation` `installation` | open |
| [ci-matrix-laravel-version-constraint-alignment](todos/ci-matrix-laravel-version-constraint-alignment.md) | Align composer.json Laravel constraint with CI testability | ryan | 2026-09-06 | `ci` `composer` `owner-decision` | open |
| [daemon-signal-handling-implementation](todos/daemon-signal-handling-implementation.md) | Decide and implement process signal handling (SIGINT/SIGTERM) for daywatch:agent | unassigned | 2026-09-10 | `daemon` `operations` `resilience` | open |

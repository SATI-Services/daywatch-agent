---
title: phpunit.xml APP_ENV, DB_CONNECTION, DB_DATABASE, DB_URL pins forced to prevent process-env override
tags: [testing, safety, config]
status: verified 2026-10-23 auto
source: [docs/sessions/will/2026-09-13-db-env-force-pins.md]
as_of: bd5f9eb69 2026-10-23
---
`phpunit.xml` sets `force="true"` on `APP_ENV=testing`, `DB_CONNECTION=testing`, `DB_DATABASE=:memory:`, and `DB_URL=""` (empty). Without `force`, PHPUnit's PhpHandler applies unforced `<env>` only when `getenv($name) === false`, so inherited process environment overrides the pin.

**Why:** 2026-09-13 incident: leaked `DB_CONNECTION=mysql DB_DATABASE=aid_pm` in process env silently overrode test pins and dropped a production database in RefreshDatabase suite.

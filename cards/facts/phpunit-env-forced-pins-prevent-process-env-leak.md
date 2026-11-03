---
title: phpunit.xml force="true" pins prevent process-env override on DB settings
tags: [testing, safety]
status: verified 2026-11-03 auto
source: [phpunit.xml]
as_of: 5ce2311ca 2026-11-02
---
APP_ENV, DB_CONNECTION, DB_DATABASE and DB_URL are force-pinned in phpunit.xml to `testing`, `testing`, `:memory:` and an empty string respectively, so the suite runs on in-memory sqlite whatever the shell exports.

Without `force="true"`, phpunit treats the pin as a default only — if the process environment exports a DB_* value, it silently wins and redirects RefreshDatabase and migrations to a real database.

**Why:** The board DB incident of 2026-09-13 dropped a production database when a DB_* value inherited from the process environment silently won over the phpunit pin.

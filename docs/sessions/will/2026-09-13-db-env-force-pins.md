# 2026-09-13 — Force the sqlite/test pin so a leaked DB_* cannot reach production

**Operator:** will
**Agent:** kimi (unreported)

## What

`phpunit.xml` already pinned `APP_ENV=testing`, `DB_CONNECTION=testing`,
`DB_DATABASE=:memory:` — but **unforced**. PHPUnit's `PhpHandler` applies an
unforced `<env>` only when `getenv($name) === false`, so a `DB_*` value leaked
into the process environment silently overrides the pin. That is exactly how
the 2026-09-13 board incident dropped a production database (`aid_pm`, 30
tables) from a RefreshDatabase suite.

Fix (Shape A — pins existed, force them):

- `force="true"` on `APP_ENV`, `DB_CONNECTION`, `DB_DATABASE`.
- New `<env name="DB_URL" value="" force="true"/>` — an inherited
  `DB_URL`/`DATABASE_URL` overrides Laravel connection config; the forced empty
  pin neutralises it.
- Comment above the pins recording why `force` is load-bearing (incident ref).

Verified PHPUnit 12.5.33 (Pest 4.7.8) supports `force`
(`vendor/phpunit/phpunit/src/TextUI/Configuration/PhpHandler.php:140`).

## Evidence

Scratch probe test (created, run, deleted — not committed) under
`DB_CONNECTION=mysql DB_DATABASE=aid_pm DB_HOST=127.0.0.1 DB_URL=mysql://…/aid_pm`:

- **With force (new):** `getenv(DB_CONNECTION)` → `testing`,
  `getenv(DB_DATABASE)` → `:memory:`, `getenv(DB_URL)` → `""`, resolved driver
  `sqlite`, database `:memory:`.
- **Without force (old pins):** `getenv(DB_CONNECTION)` → `mysql`,
  `getenv(DB_DATABASE)` → `aid_pm` — the leak won at the env layer. (This
  package's `TestCase::defineEnvironment()` still pinned Laravel config to
  sqlite, but anything resolving from `env('DB_*')` directly — the incident
  mechanism — was exposed.)

Suite runs: full suite green under the hostile env and normally —
**300 passed (968 assertions)** both ways (`composer test`).

## Commit

- (SHA recorded post-commit below in git history)

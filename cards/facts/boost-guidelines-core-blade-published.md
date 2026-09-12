---
title: AGENTS.md requires Boost core.blade.php to cover config publish, env vars, daemon, facade API
tags: [boost, docs]
status: verified 2026-09-11 auto
source: [AGENTS.md]
as_of: cdd56d1aa 2026-09-10
---
AGENTS.md fixes the required scope of `resources/boost/guidelines/core.blade.php` (the directive is "Add" — it defines what the file must contain):

- publish `config/daywatch.php`
- set `DAYWATCH_TOKEN` / `DAYWATCH_BASE_URL` / `DAYWATCH_INGEST_URI`
- run the `daywatch:agent` daemon
- the `Daywatch::user()/sample()/report()/ignore()` API

Format: a short, example-driven overview per agentskills.io — consumer-facing, distinct from the internal engineering guide (`CLAUDE.md`/`AGENTS.md`).

**Why:** The package does not install Boost itself, but any host app running `boost:install` auto-loads these guidelines, so their scope is the consumer's onboarding surface and must stay separate from the dev guide.

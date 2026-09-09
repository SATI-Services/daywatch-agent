---
title: Add and ship Boost guidelines to resources/boost/guidelines/core.blade.php
tags: [laravel, boost, documentation, installation]
status: open
owner: unassigned
since: 2026-09-09
source: [CLAUDE.md]
as_of: dbb7df637 2026-09-09
---
Add the consumer-facing Boost guidance file at `resources/boost/guidelines/core.blade.php`, publishable by `boost:install`. The guidance should be short and example-driven and cover: publish `config/daywatch.php`, set `DAYWATCH_TOKEN` / `DAYWATCH_BASE_URL` / `DAYWATCH_INGEST_URI`, run the `daywatch:agent` daemon, and use the `Daywatch::user()/sample()/report()/ignore()` API.

Why it is open: the engineering guide instructs to "Add `resources/boost/guidelines/core.blade.php`" but the file is not present yet. Done = the blade file exists in the repo, is included in the package distribution, and `boost:install` (or equivalent consumer flow) publishes it and the README/docs reference it.

---
title: Ship Boost guidelines to resources/boost/guidelines/core.blade.php
tags: [boost, docs, consumer-facing]
status: open
owner: unassigned
since: 2026-10-31
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
Daywatch is a package, not an app — it does not install Boost itself — but it must ship Boost guidelines so any host app that runs `boost:install` auto-loads how to use Daywatch.

Done = `resources/boost/guidelines/core.blade.php` exists as a short, example-driven overview per agentskills.io, covering: publish `config/daywatch.php`; set `DAYWATCH_TOKEN` / `DAYWATCH_BASE_URL` / `DAYWATCH_INGEST_URI`; run the `daywatch:agent` daemon; and the `Daywatch::user()/sample()/report()/ignore()` API. It is consumer-facing and distinct from this dev guide.

**Why open:** AGENTS.md states the requirement as an instruction to add the file; the guideline file is not yet written.

---
title: Service provider auto-registers via Laravel package discovery
tags: [laravel, auto-wiring, installation]
status: verified 2026-09-09 auto
source: [README.md]
as_of: 16a6c506a 2026-09-06
---
The Daywatch Agent service provider auto-registers via Laravel's package discovery mechanism; no manual registration required in `config/app.php`.

**Why:** Zero-config installation is standard for Laravel 11+; consumers don't need to touch config files.

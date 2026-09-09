---
title: Service provider and Daywatch facade auto-register via package discovery
tags: [installation, laravel, auto-wiring]
status: verified 2026-09-09 auto
source: [README.md]
as_of: 16a6c506a 2026-09-06
---
The service provider and `Daywatch` facade auto-register via Laravel package discovery. Optionally publish config via `php artisan vendor:publish --tag=daywatch-config`.

**Why:** Zero-config installation experience for consumers; publish only if customization is needed.

---
title: Patterns.php (new) extracts needle/glob precompile shared by all filter sensors
tags: [filtering, sensor-internal]
status: verified 2026-10-23 auto
source: [docs/sessions/ryan/2026-10-06-filtering-jobs-and-queries.md]
as_of: bd5f9eb69 2026-10-23
---
`src/Support/Patterns.php` normalises array-or-CSV filter options. Plain `*substring*` globs become `str_contains` needles (hot path: every query, cache event, dispatch); real glob structure falls back to `Str::is()`. `Patterns::from()` replaces the provider's old `patterns()` helper.

**Why:** Extracted from CacheEventSensor so every filter shares precompilation logic.

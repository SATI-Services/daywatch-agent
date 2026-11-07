---
title: Patterns precompiles Str::is() ignore patterns into needles and globs once
tags: [core, performance, filtering]
status: verified 2026-11-07 auto
source: [src/Support/Patterns.php]
as_of: a80ce4d1c 2026-11-07
---
`Patterns` backs the per-sensor `ignore` options (`daywatch.sensors.*.ignore`: cache keys, query SQL, job names). It splits the `Str::is()` patterns once at construction into plain `*substring*` needles matched with `str_contains`, and keeps `Str::is()` only for patterns with real glob structure.

**Why:** These filters run on hot host paths — every cache event, every query, every job dispatch — and `Str::is()` compiles a fresh regex per call. The common shape, a plain `*substring*` glob like the `*illuminate:*` cache-key default, reduces to a `str_contains` check.

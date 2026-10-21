---
title: Per-sensor record sampling via `sensors.{class}.sample` gates seven high-volume child-record sensor types
tags: [sampling, config]
status: verified 2026-10-21 auto
source: [docs/sessions/status/ryan.md]
as_of: 7e1999d1e 2026-10-20
---
Per-sensor record sampling is configured per sensor class as `sensors.{class}.sample` and applies to the seven high-volume child-record sensor types. The option is backed by seven `DAYWATCH_*_SAMPLE_RATE` environment variables. It shipped in release `1.2.0`, tagged 2026-10-12.

**Why:** Those seven child-record sensor types are the high-volume ones, so collection is tunable per sensor type rather than only per execution.

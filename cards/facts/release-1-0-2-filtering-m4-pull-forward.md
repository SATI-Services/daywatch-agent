---
title: Release 1.0.2 carries M4 filtering pull-forward — ignore_queries, ignore_query_patterns, ignore_job_names
tags: [release, filtering, changelog]
status: verified 2026-10-31 auto
source: [docs/sessions/ryan/2026-10-06-release-1.0.2.md]
as_of: f03ba15a2 2026-10-30
---
Release 1.0.2 (2026-10-06, `Daywatch::VERSION` in `src/Daywatch.php` bumped 1.0.1 → 1.0.2) carries the M4 filtering pull-forward landed earlier the same day (`78209d5`): `filtering.ignore_queries` wired, new `filtering.ignore_query_patterns` (default drops framework-internal tables) and `filtering.ignore_job_names` (queued-job AND job-attempt), plus the shared `Support\Patterns` matcher. Everything else in `1.0.1..1.0.2` is docs/harness only — no other code changes.

**Why:** M4 filtering landed the same day as the version bump; the release captures the feature.

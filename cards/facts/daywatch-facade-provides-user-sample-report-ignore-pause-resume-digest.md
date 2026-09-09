---
title: Daywatch facade provides user(), sample(), dontSample(), report(), ignore(), pause(), resume(), digest()
tags: [facade, api, runtime]
status: verified 2026-09-09 auto
source: [README.md, CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
The `Daywatch` facade inside a request, job, or command provides: `Daywatch::user($id)`, `Daywatch::sample()` (force-keep), `Daywatch::dontSample()` (force-drop), `Daywatch::report($e)` (record exception, escapes sampling), `Daywatch::ignore($e)` (suppress exception), `Daywatch::pause()`, `Daywatch::resume()`, and `Daywatch::digest()` (flush to daemon now). Every call is safe and never throws.

**Why:** Gives developers programmatic control over telemetry collection and sampling decisions.

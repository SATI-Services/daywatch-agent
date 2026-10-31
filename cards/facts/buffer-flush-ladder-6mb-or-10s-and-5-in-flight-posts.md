---
title: The daemon's buffer flushes at ≥ 6 MB or 10 s
tags: [daemon, buffer, dispatch]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
The daemon buffers record JSON at string level and flushes at ≥ 6 MB or 10 s.

**Why:** The daemon never re-parses record JSON — string-level buffer concatenation is how records accumulate until the flush threshold trips.

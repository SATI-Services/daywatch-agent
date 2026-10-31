---
title: Daemon never re-parses record JSON; uses string-level buffer concatenation
tags: [daemon, performance, buffer]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
The daemon never re-parses record JSON — records are concatenated at the string level in the buffer, and the concatenated batch is gzipped and POSTed in one upload.

**Why:** The daemon treats records as opaque strings end to end; only the batch bounds (flush ≥ 6 MB or 10 s) apply before upload.

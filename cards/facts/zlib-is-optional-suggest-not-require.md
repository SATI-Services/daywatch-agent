---
title: ext-zlib is a suggest, not a require; gzencode guarded by function_exists
tags: [dependencies, zlib, resilience]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md]
as_of: 16a6c506a 2026-09-06
---
`ext-zlib` is declared as a `suggest`, not a `require`. The daemon's gzip upload is guarded with `function_exists('gzencode')`, so a host missing zlib still installs and runs; only the daemon's upload pauses (drop + log) instead of fatalling.

**Why:** zlib is near-universal but not guaranteed. A host missing it should still be able to install and collect (uncompressed or paused), not fail completely.

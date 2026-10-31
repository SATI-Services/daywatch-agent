---
title: Missing ext-zlib pauses the daemon's gzip upload; gzencode stays behind function_exists
tags: [compression, zlib, daemon]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: da814d4f7 2026-10-31
---
`ext-zlib` is a `suggest`, not a `require`: a host missing it still installs and runs — sensors, buffer and socket never touch zlib, so only the daemon's gzip upload is affected. `IngestDispatcher` guards `gzencode` with `function_exists`, so a missing extension pauses upload (drop + log) instead of fatalling. The gzip wire format itself is unchanged — dropping compression would be a cross-repo contract change (docs-first via the `daywatch-payloads` skill), not a package-local one.

**Why:** zlib is near-universal but not guaranteed; the package must stay un-crashable, so a missing extension degrades the upload rather than throwing into the host.

---
title: Missing ext-zlib pauses the daemon's gzip upload instead of fatalling
tags: [zlib, daemon, gotcha]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
`ext-zlib` is a `suggest`, not a `require`: a host missing it still installs and runs — sensors, buffer and socket never touch zlib — so only the daemon's gzip upload is affected. `IngestDispatcher` guards `gzencode` with `function_exists`, so a missing extension pauses upload (drop + log) instead of fatalling.

**Why:** zlib is near-universal but not guaranteed, so the failure mode is a paused upload, not a fatal error.

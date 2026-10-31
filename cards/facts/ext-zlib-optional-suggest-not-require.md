---
title: ext-zlib is a suggest, not a require; gzencode guarded by function_exists
tags: [dependencies, compression, zlib]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: 41d44b355 2026-10-31
---
**`ext-zlib` is a `suggest`, not a `require`** — zlib is near-universal but not guaranteed, so a host missing it still installs and runs (sensors/buffer/socket never touch zlib); only the daemon's gzip upload is affected. `IngestDispatcher` guards `gzencode` with `function_exists` so a missing extension pauses upload (drop + log) instead of fatalling.

**Why:** A host without zlib should still install, run and collect records; the only casualty is the daemon's gzip upload, which pauses and drops the batch with a log line rather than crashing.

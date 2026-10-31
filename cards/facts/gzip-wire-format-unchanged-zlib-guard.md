---
title: Missing ext-zlib pauses the daemon's gzip upload; gzip wire format unchanged
tags: [compression, wire-contract, zlib]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
`ext-zlib` is a `suggest`, not a `require`: a host missing it still installs and runs — sensors, buffer and socket never touch zlib, so only the daemon's gzip upload is affected. `IngestDispatcher` guards `gzencode` with `function_exists`, so a missing extension pauses upload (drop + log) instead of fatalling. The gzip wire format itself is unchanged — dropping compression would be a cross-repo contract change (docs-first via the `daywatch-payloads` skill), not a package-local one.

**Why:** The wire contract is system-wide; dropping compression is a docs-first cross-repo change, not a package-local adaptation.

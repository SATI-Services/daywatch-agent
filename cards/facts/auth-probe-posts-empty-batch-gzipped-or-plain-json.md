---
title: AuthProbe POSTs empty batch gzipped with zlib, plain JSON as fallback
tags: [auth, daemon, probe]
status: verified 2026-10-27 auto
source: [src/Daemon/AuthProbe.php]
as_of: 28ce500a6 2026-10-26
---
AuthProbe POSTs `{"records":[]}` gzipped when zlib is present via function_exists('gzencode'); falls back to plain JSON if absent or gzencode fails.

**Why:** Both ingest implementations accept gzipped and plain JSON equally. Compression is opportunistic; the probe must never fail due to missing zlib.

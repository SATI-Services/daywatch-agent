---
title: BatchBuffer flushes on ≥6 MB or age ≥10 s, whichever first
tags: [daemon, batching]
status: verified 2026-11-01 auto
source: [src/Daemon/BatchBuffer.php]
as_of: 2ae2d15e3 2026-10-31
---
Flush triggers: accumulated size ≥ `flushBytes` (default 6 MB), or `flushIntervalSeconds` (default 10) elapsed since the first byte was buffered.

**Why:** Age-based flush ensures records don't sit indefinitely even in low-traffic scenarios; size ceiling prevents unbounded memory growth.

---

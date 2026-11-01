---
title: BatchBuffer accumulates record JSON via string concatenation, never re-parsing
tags: [daemon, performance, wire-format]
status: verified 2026-11-01 auto
source: [src/Daemon/BatchBuffer.php, docs/WORKFLOW.md]
as_of: 2ae2d15e3 2026-10-31
---
BatchBuffer strips outer brackets from each incoming digest `[{...},{...}]`, comma-joins the inner objects into one growing string, and wraps them back into `{"records":[...]}` at flush. Record JSON is never decoded — O(bytes) memcpy only, no parsing cost at 6 MB scale.

**Why:** Parsing-free accumulation scales to the daemon's 6 MB flush ceiling without decode overhead. Memory cost stays proportional to payload size, not object count.

---

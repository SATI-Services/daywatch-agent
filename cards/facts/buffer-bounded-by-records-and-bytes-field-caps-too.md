---
title: In-process buffer bounded by records AND bytes; field caps let one record carry megabytes
tags: [buffer, memory, bounds]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
The in-process buffer is bounded by **records AND bytes** (`ingest.event_buffer` / `ingest.buffer_bytes`): field caps let one record carry megabytes, so a count alone is not a memory bound on the host.

**Why:** A record count alone is not a host memory bound — field caps let a single record carry megabytes, so both bounds together do.

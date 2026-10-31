---
title: Daemon keeps O(1) counters: records received/buffered, buffered bytes, batches/records sent, send failures, retries, last flush time/size, base URL
tags: [daemon, stats, observability]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
The daemon keeps O(1) counters (`Daemon/DaemonStats`): records received/buffered, buffered bytes, batches/records sent, send failures, retries, last flush time/size, plus the configured base URL (reported verbatim since the daemon can't know whether the Laravel app or the Java relay answers it).

**Why:** O(1) counters keep memory overhead constant. The base URL is reported because the daemon cannot know which ingest implementation is responding.

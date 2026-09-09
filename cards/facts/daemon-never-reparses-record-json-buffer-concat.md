---
title: Daemon never re-parses record JSON; uses string-level buffer concatenation
tags: [daemon, performance, ingest, batching]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
The daemon never re-parses record JSON. It uses string-level buffer concatenation to batch records, flushing ≥6 MB or 10 s, with ≤5 in-flight POSTs. Retry ladder and 503 `stop` NullBuffer pause contract per `system/agent-protocol.md` §6.

**Why:** Avoiding JSON re-parse speeds batching and reduces memory churn. The pause contract lets the ingest shed load without losing records.

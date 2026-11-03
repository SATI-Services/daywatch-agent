---
title: Batch id minted once, reused across retries for server dedup via Redis SETNX
tags: [daemon, protocol, batching]
status: verified 2026-11-03 auto
source: [src/Daemon/Batch.php]
as_of: 9cfb6374a 2026-11-03
---
The Batch id (Daywatch-Batch-Id header) is minted once at creation and reused across all retry attempts. The server deduplicates redelivery via Redis SETNX (agent protocol §6).

**Why:** Ensures idempotent delivery — retries don't create duplicate batches on the server side.

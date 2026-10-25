---
title: IngestDispatcher applies the agent protocol §6 response contract
tags: [daemon, ingest, protocol]
status: verified 2026-10-25 auto
source: [src/Daemon/IngestDispatcher.php]
as_of: 915df1abb 2026-10-25
---
`IngestDispatcher::handleResponse()` branches on the ingest POST status per agent protocol §6:

- 2xx: discard the batch, reset `consecutiveFailures`, clear `paused`.
- 401: count `authFailed`, drop the batch.
- 413 and 422: drop the poison batch.
- 429: retain and retry after `retry_in`, defaulting to `LADDER[0]` = 2.5s when the field is absent.
- 503 with `stop: true`: pause telemetry for `refresh_in` (default `DEFAULT_REFRESH_IN` = 900s); the batch is not retained.
- Any other 5xx, and network errors: retry on `LADDER` [2.5, 5, 10, 15, 30, 60, 120, 240, 300]s. Past the nine
  ladder steps `delayFor()` holds at `HOLD_SECONDS` = 300s for failures 10–12, then at `MAX_SECONDS` = 3600s.
- Any other 4xx: dropped as unexpected.

Retained batches are capped at `retainCapBytes` (33,554,432 B): overflow evicts oldest first only while more
than one batch is held — a single batch over the cap is dropped outright.

**Why:** Transport and timers are injected so the whole contract is deterministically testable with no sockets
and no sleeps.

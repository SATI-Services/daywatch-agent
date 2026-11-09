---
title: QueuedJobRecord `_group` is xxh128 of the untruncated job name
tags: [wire-contract, records, grouping]
status: verified 2026-11-09 auto
source: [src/Records/QueuedJobRecord.php]
as_of: 20af138a0 2026-11-09
---
The `queued-job` record's `_group` is `xxh128(name)`, taken from the untruncated job name, so dispatches sharing a name share a group regardless of `queue` or `connection`. The wire `name` field is `Truncate::tiny(name)`, so do not recompute the group from it.

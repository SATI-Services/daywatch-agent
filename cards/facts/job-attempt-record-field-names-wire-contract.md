---
title: JobAttemptRecord field names are wire contract
tags: [wire, record, contract]
status: verified 2026-11-08 auto
source: [src/Records/JobAttemptRecord.php]
as_of: be428b1b8 2026-11-08
---
`JobAttemptRecord::toArray()` field names are wire contract: `job_id`, `attempt_id`, `attempt`, `name`, `connection`, `queue`, `status`, `duration`, then the shared `Counters::tail()` block. `attempt_id` is the attempt's own execution id, carried under the inherited `trace_id`, so the record duplicates no `execution_*` fields.

**Why:** They define the payload shape of the `job-attempt` record (agent protocol §2), so renaming one changes the wire format.

---
title: JobAttemptRecord field names are wire contract
tags: [wire, record, contract]
status: verified 2026-11-08 auto
source: [src/Records/JobAttemptRecord.php]
as_of: c34aba0b3 2026-11-08
---
`JobAttemptRecord::toArray()` field names are wire contract.

**Why:** They define the payload shape of the `job-attempt` record (agent protocol §2), so a rename changes the wire format.

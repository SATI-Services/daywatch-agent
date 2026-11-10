---
title: UserRecord is envelope-only — no `_group`, `trace_id`, or `execution_*` fields
tags: [records, wire-contract]
status: verified 2026-11-10 auto
source: [src/Records/UserRecord.php]
as_of: 97b165f2d 2026-11-10
---
`UserRecord` carries only the envelope plus `id`, `name`, `username`. `toArray()` merges its own fields into the instance call `$this->envelope->minimal('user')` — it is not a static `Envelope::minimal('user')` call. The record has no `_group`, `trace_id`, or `execution_*` fields.

**Why:** The `user` record is emitted once per execution, only when an authenticated user is present — a singleton fact for that execution, not a traced child event. The user id also travels inline on every other record.

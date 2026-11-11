---
title: Group._group recipes are wire contract — seven fingerprint shapes for record types
tags: [wire, grouping]
status: verified 2026-11-11 auto
source: [src/Support/Group.php]
as_of: 43f0e0487 2026-11-11
---
`Group` computes `_group` fingerprints as xxh128 hex digests, client-side. Seven recipes:

- request: xxh128( join('|', methods) . ',' . domain . ',' . path )
- query: xxh128( connection . ',' . normalizeSql(sql) )
- exception: xxh128( class . '|' . code . '|' . file . '|' . line )
- name: xxh128( value ) — for command, queued-job, job-attempt (by job/command name) and mail, notification (by class)
- cache-event: xxh128( store . '|' . key )
- outgoing-request: xxh128( host )
- scheduled-task: xxh128( name . '|' . cron . '|' . timezone )

`normalizeSql` collapses `IN (?, ?, …)` lists and bulk `VALUES (…), (…)` tuples so parameter count doesn't fragment groups; the transmitted `sql` field stays raw and bindings are never substituted.

**Why:** Grouping is deterministic and cheap for the ingest/ClickHouse GROUP BYs. These recipes are WIRE CONTRACT — see the `daywatch-payloads` skill before changing one.

---
title: QueryRecord _group is built from the normalized SQL by Group::query()
tags: [wire-contract, records, grouping]
status: verified 2026-11-09 auto
source: [src/Records/QueryRecord.php]
as_of: 58df7f0f2 2026-11-09
---
`QueryRecord`'s `_group` is produced by `Group::query($this->connection, $this->sql)` — the class docblock states `_group` uses the normalized SQL, and the connection is passed into the same call. The record's own `sql` field is transmitted raw with `?` placeholders; bindings are never substituted.

**Why:** Grouping is meant to collapse identical query shapes — since `sql` goes over the wire raw with `?` placeholders, the normalized form is what makes two executions of the same query comparable.

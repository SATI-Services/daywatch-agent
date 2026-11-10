---
title: UserRecord field names are wire contract
tags: [wire-contract, records]
status: verified 2026-11-10 auto
source: [src/Records/UserRecord.php]
as_of: ebeca95d9 2026-11-10
---
`UserRecord`'s field names (`id`, `name`, `username`) are wire contract — the class docblock says so; do not add or rename them.

**Why:** The names travel in the wire payload: `toArray()` emits them under the `user` envelope (`Envelope::minimal('user')`), each passed through `Truncate::tiny()`.

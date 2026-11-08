---
title: CommandRecord _group is xxh128(name)
tags: [wire-contract, protocol, grouping]
status: verified 2026-11-08 auto
source: [src/Records/CommandRecord.php]
as_of: 2cf41c87a 2026-11-08
---
CommandRecord uses `Group::name($this->name)` to compute `_group`, which is xxh128 of the command name.

**Why:** Grouping commands by name aggregates execution profiles; xxh128 keeps the group stable across runs and versions.

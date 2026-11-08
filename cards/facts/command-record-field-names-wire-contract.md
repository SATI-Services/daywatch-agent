---
title: Command record field names are wire contract
tags: [wire-contract, protocol, records]
status: verified 2026-11-08 auto
source: [src/Records/CommandRecord.php]
as_of: 2cf41c87a 2026-11-08
---
CommandRecord.toArray() emits field names that form the wire contract: class, name, command, exit_code, duration, bootstrap, action, terminating, plus envelope and counters tail.

**Why:** Field names in sensor records are distributed wire contracts; changes require cross-repo coordination.

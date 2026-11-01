---
title: Verify BatchBuffer.measure() efficiency against pathological payloads
tags: [daemon, testing, performance]
status: open
owner: unassigned
since: 2026-10-31
source: [src/Daemon/BatchBuffer.php]
as_of: 2ae2d15e3 2026-10-31
---
The `measure()` method strips brackets without buffering to peek at incoming size. Confirm it handles deeply nested or malformed JSON efficiently and never regresses in a high-throughput scenario. Done = test added for edge cases (empty array, missing brackets, whitespace variants) and benchmarked against the add() path.
---

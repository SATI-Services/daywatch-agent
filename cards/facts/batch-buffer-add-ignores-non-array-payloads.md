---
title: BatchBuffer.add() ignores non-array payloads and returns 0 record bytes
tags: [daemon, batching, wire]
status: verified 2026-11-01 auto
source: [src/Daemon/BatchBuffer.php]
as_of: 5f4d0423d 2026-11-01
---
`add()` trims the payload and requires a leading `[` and trailing `]`; anything else (non-array, empty, single character) is dropped, returns `0`, and leaves the buffer untouched.

**Why:** A garbled or bracket-less digest contributes no records and raises no error, so buffer size and record counters stay honest without the daemon having to validate JSON.

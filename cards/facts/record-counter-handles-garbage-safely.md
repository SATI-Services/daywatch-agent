---
title: RecordCounter.count() handles malformed payloads gracefully, never throws
tags: [daemon, robustness]
status: verified 2026-11-04 auto
source: [src/Daemon/RecordCounter.php]
as_of: 90ae942b6 2026-11-04
---
RecordCounter.count() returns 0-N on any input, including garbage, unterminated strings, and truncated JSON. Advances past escaped quotes correctly (odd-run backslash rule). Never throws.

**Why:** Counting is a C-speed structural scan, not a parse; malformed payloads must not crash the daemon's observability.

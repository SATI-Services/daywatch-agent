---
title: ConsoleDashboard clamps each line to terminal width when width is > 0
tags: [daemon, console, rendering]
status: verified 2026-10-29 auto
source: [src/Daemon/ConsoleDashboard.php]
as_of: 541096adf 2026-10-29
---
`ConsoleDashboard::clamp()` truncates each line only when the injected `width` callable returns > 0; at `<= 0` (the default callable returns 0) lines are returned unchanged. An over-long line is cut to `max(1, width - 1)` characters plus `…`, measured with `mb_strlen()` and cut with `mb_substr()` so multi-byte text is not split mid-character.

**Why:** A line the terminal soft-wraps costs an extra physical row, and `paint()` rewinds the cursor by the newline count only — so the panel walks down the screen, overwriting itself. A multi-address ECONNREFUSED in an auth error is long enough to trigger it; truncating keeps one logical line on one physical row, which is what makes the newline count a correct cursor offset.

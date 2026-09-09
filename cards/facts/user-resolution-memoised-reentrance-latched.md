---
title: User resolution is memoised and re-entrancy latched
tags: [user, resolution, reentrance, safety]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
`Daywatch::user()` memoises the result and latches re-entrancy. It treats a string/int as an ID even when it happens to name a global function (e.g. `is_callable('info')` is true — honouring it would invoke host code), and a resolver that logs or queries can't recurse back through a sensor.

**Why:** Prevents accidental callback invocation on function-name strings and infinite loops if a sensor callback triggers user resolution.

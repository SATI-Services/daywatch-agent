---
title: Daywatch user() treats a string or int as an id even when it names a global function
tags: [user, resolution, gotcha]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
`Daywatch::user()` treats a string/int as an id even when it happens to name a global function — `is_callable('info')` is true.

**Why:** Honouring the callable would invoke host code.

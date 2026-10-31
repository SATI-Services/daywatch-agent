---
title: Daywatch::user() takes a string/int as an id even when it names a global function
tags: [resolution, user, gotcha]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
`Daywatch::user()` treats a string/int as an id even when the value happens to name a global function — `is_callable('info')` is true.

**Why:** Honouring an `is_callable` name would invoke host code instead of recording a user id.

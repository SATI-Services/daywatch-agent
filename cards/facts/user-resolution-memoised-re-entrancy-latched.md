---
title: User resolution memoised and re-entrancy latched; string/int taken as id even when it names a global function
tags: [resolution, user, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
`Daywatch::user()` resolution is memoised and re-entrancy latched. It treats a string/int as an id even when it happens to name a global function (`is_callable('info')` is true) — honouring it would invoke host code — and a resolver that logs or queries cannot recurse back through a sensor.

**Why:** A resolver that logs or queries would otherwise trigger a sensor that calls `user()` again; latching prevents the recursion, and honouring an `is_callable` name would invoke host code.

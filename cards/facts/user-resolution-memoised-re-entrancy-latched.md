---
title: User resolution is memoised and re-entrancy latched
tags: [user, resolution, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: da814d4f7 2026-10-31
---
`Daywatch::user()` resolution is memoised and re-entrancy latched.

**Why:** A resolver that logs or queries must not recurse back through a sensor.

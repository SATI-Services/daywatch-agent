---
title: User resolution is re-entrancy latched — a resolver that logs or queries can't recurse back through a sensor
tags: [user, resolution, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: a914f6c40 2026-10-31
---
`Daywatch::user()` resolution is re-entrancy latched: if the resolver itself logs or queries, those framework events cannot recurse back through a sensor into user resolution.

**Why:** A resolver that fires framework events would otherwise re-enter `Daywatch::user()` via the sensors and recurse without bound. Memoisation of the resolved user is a separate fact — see `user-resolution-memoised`.

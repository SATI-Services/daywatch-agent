---
title: User resolution is re-entrancy latched so a resolver cannot recurse through a sensor
tags: [user, resolution, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: a914f6c40 2026-10-31
---
`Daywatch::user()` resolution is re-entrancy latched: a resolver that logs or queries cannot recurse back through a sensor.

**Why:** A user resolver that logs or queries fires the framework events sensors observe; without the latch, resolution would re-enter itself through a sensor. Non-negotiable behavior per AGENTS.md.

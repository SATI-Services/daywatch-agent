---
title: Sensor singletons resolved once at boot; runtime config()->set() has no effect
tags: [sensor, testing, gotcha]
status: verified 2026-10-23 auto
source: [docs/sessions/ryan/2026-10-06-filtering-jobs-and-queries.md]
as_of: bd5f9eb69 2026-10-23
---
SensorManager::register() resolves sensor singletons once at boot, so `config()->set(...)` in a test or at runtime has no effect on an already-resolved sensor. Container-wiring tests must `forgetInstance()` and re-resolve.

**Why:** Config changes made after boot are not observed by an already-resolved sensor; tests that patch config must re-resolve or they assert against stale wiring.

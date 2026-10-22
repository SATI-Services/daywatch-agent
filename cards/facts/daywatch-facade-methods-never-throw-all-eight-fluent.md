---
title: All eight Daywatch facade methods never throw and stay fluent; swallow all Core failures
tags: [facade, uncrashable, fluent]
status: verified 2026-10-22 auto
source: [docs/sessions/ryan/2026-10-12-entry-point-and-facade-guard-tests.md]
as_of: 2fb153d96 2026-10-21
---
All eight methods on the Daywatch facade (`user()`, `sample()`, `dontSample()`, `report()`, `ignore()`, `pause()`, `resume()`, `digest()`) never throw and return `$this`, staying fluent. They form the second line of defence behind Core's own guards: if Core fails to build, the provider's guarded factory resolves a disabled no-op Daywatch instead of throwing into the host app.

**Why:** The package is uncrashable; every public API entry point must swallow failures and be safe to call unconditionally.

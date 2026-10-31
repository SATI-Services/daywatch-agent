---
title: AgentServiceProvider register() stashes boot exceptions and reports them in boot()
tags: [provider, boot, uncrashable]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: a914f6c40 2026-10-31
---
`AgentServiceProvider::register()` catches boot exceptions during registration, stashes them, and reports them in `boot()` instead of letting them escape.

**Why:** Cardinal rule: this package must be un-crashable — any code path that can throw into the host app is a P0 bug.

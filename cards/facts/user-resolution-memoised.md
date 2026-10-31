---
title: Daywatch::user() resolution is memoised
tags: [user, resolution, architecture]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: a914f6c40 2026-10-31
---
User resolution behind `Daywatch::user()` is memoised — the resolved user is cached and reused rather than the resolver re-running on every call.

**Why:** The resolver may itself log or query; memoisation keeps that side-effecting resolution from repeating. The companion re-entrancy latch is a separate fact — see `user-resolution-memoised-re-entrancy-latched`.

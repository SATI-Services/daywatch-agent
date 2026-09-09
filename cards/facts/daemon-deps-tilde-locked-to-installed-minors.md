---
title: Daemon react dependencies are tilde-locked; patch drift only
tags: [dependencies, react, daemon, pinning]
status: verified 2026-09-09 auto
source: [CLAUDE.md]
as_of: 16a6c506a 2026-09-06
---
The daemon `react/*` dependencies are pinned tilde-to-lock: `react/{event-loop ~1.6.0, socket ~1.17.0, promise ~3.3.0, stream ~1.4.0}`, matching the `require-dev` idiom. Only patch-level drift is allowed.

**Why:** Daemon stability is load-bearing; minor version changes could introduce breaking changes. Tilde pins allow patch-level security fixes without manual updates.

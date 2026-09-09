---
title: Never add react/http; it pins psr/http-message ^1.0 conflicting with Laravel 13
tags: [dependencies, react, conflicts, constraints]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md]
as_of: 16a6c506a 2026-09-06
---
Never add `react/http` as a dependency. It pins `psr/http-message ^1.0`, which conflicts with Laravel 13 host's `^2.0`. The daemon POSTs raw HTTP/1.1 over `react/socket` for exactly this reason. The `react/*` daemon deps stay tilde-locked to their installed minors.

**Why:** This is a known incompatibility; adding `react/http` would break Laravel 13 hosts. The low-level socket approach avoids the PSR version collision entirely.

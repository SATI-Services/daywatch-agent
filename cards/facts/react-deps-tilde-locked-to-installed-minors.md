---
title: react/* daemon deps are tilde-locked to their installed minors
tags: [dependencies, daemon, constraints]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: da814d4f7 2026-10-31
---
The `react/*` daemon deps are pinned tilde-to-lock to their installed minors — patch drift only: `react/event-loop ~1.6.0`, `react/socket ~1.17.0`, `react/promise ~3.3.0`, `react/stream ~1.4.0`.

**Why:** Only the host-compat constraints stay permissive (`illuminate/support ^11|^12|^13`, `php ^8.2`); everything else in `require` is deliberately locked.

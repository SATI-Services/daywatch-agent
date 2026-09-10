---
title: TTY mode feeds bounded RecentLog ring buffer; last N lines shown on dashboard
tags: [daemon, ui, operations]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: 80c896873 2026-09-09
---
When the daemon runs attached to a TTY, its logger feeds a bounded `Daemon/RecentLog` ring buffer, which holds the last N log lines displayed on the dashboard.

**Why:** Keeps recent activity visible without scrolling; bounded size prevents memory growth.

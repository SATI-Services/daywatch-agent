---
title: RecentLog is a bounded ring buffer of daemon log lines; capacity configurable
tags: [daemon, dashboard, logging]
status: verified 2026-11-04 auto
source: [src/Daemon/RecentLog.php]
as_of: 23d988245 2026-11-04
---
RecentLog holds the most recent N log lines in a ring buffer (default capacity 10). push() evicts the oldest line when capacity is exceeded. lines() returns the retained tail, oldest first. total() counts all lines ever pushed, including evicted ones.

**Why:** ConsoleDashboard feeds here instead of scrolling stdout, so the live display can show only recent lines (auth errors, retries, pauses) without the console growing unbounded.

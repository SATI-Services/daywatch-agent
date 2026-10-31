---
title: Records counted once per digest at the frame boundary via C-speed string scan
tags: [daemon, stats, performance]
status: verified 2026-10-31 auto
source: [AGENTS.md]
as_of: 41d44b355 2026-10-31
---
Records are counted once per digest at the frame boundary by `Daemon/RecordCounter`, a C-speed string scan that honours JSON strings and escapes. Record JSON is never re-parsed.

**Why:** Counting happens while scanning the framed buffer as a string, so the daemon never re-parses record JSON.

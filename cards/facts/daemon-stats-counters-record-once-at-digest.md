---
title: Daemon/RecordCounter counts records once per digest at the frame boundary via a C-speed string scan that honours JSON strings and escapes
tags: [daemon, stats, protocol]
status: verified 2026-09-10 auto
source: [AGENTS.md]
as_of: c8cd1c517 2026-09-10
---
Records are counted once per digest at the frame boundary by Daemon/RecordCounter. The implementation is a C-speed string scan that honours JSON strings and escape sequences; record JSON is never re-parsed after buffering.

**Why:** Counting at the frame boundary with a string-level scan keeps counters accurate without the cost or risk of full deserialization, preserving the package's "never re-parse record JSON" constraint.

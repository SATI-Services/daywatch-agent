---
title: kimi-session-log-nudge hook auto-detects AID-harness projects
tags: [sessions, hooks, convention]
status: verified 2026-10-29 auto
source: [.claude/hooks/kimi-session-log-nudge.sh]
as_of: 075408cc5 2026-10-28
---
Hook checks for sessions/status or docs/sessions/status directories to detect AID-harness
projects; runs only on projects where one exists.

**Why:** Hook is generic and project-agnostic; convention detection avoids per-project config.

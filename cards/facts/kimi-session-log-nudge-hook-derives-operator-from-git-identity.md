---
title: kimi-session-log-nudge hook derives operator name from git user.name
tags: [sessions, hooks, operator]
status: verified 2026-10-29 auto
source: [.claude/hooks/kimi-session-log-nudge.sh]
as_of: 075408cc5 2026-10-28
---
The hook sets the operator from `git -C "$cwd" config user.name`; when that is empty it falls back to
`${USER:-operator}` — `$USER`, then the literal `operator` if `USER` is unset.

**Why:** Sessions are filed under the person, not the agent; git identity is the canonical
source for the operator. The fallback is written `${USER:-operator}` rather than a bare `$USER`
because bare `$USER` crashes under `set -u` when unset.

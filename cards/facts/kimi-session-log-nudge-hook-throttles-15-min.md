---
title: kimi-session-log-nudge throttles to once per 15 minutes per project
tags: [sessions, hooks, throttle]
status: verified 2026-10-29 auto
source: [.claude/hooks/kimi-session-log-nudge.sh]
as_of: 6c9699294 2026-10-29
---
The Kimi `UserPromptSubmit` hook throttles per project with a state file at
`${TMPDIR:-/tmp}/kimi_nudge_<cwd>`, where every `/`, `\` and `:` in the cwd is replaced by `_`.
If that file's mtime is under 900 seconds old the hook exits 0 without emitting a nudge; on an
emit it touches the file first.

**Why:** The hook's own comment says it throttles "so it is not emitted every single turn" — it
fires on every user prompt, so an unthrottled nudge would spend context on every turn.

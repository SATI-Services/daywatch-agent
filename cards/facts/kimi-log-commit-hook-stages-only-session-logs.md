---
title: kimi-log-commit.sh is a per-operator hook staging only the session-log dir
tags: [sessions, hooks, safety]
status: verified 2026-10-27 auto
source: [.claude/hooks/kimi-log-commit.sh]
as_of: 28ce500a6 2026-10-26
---
kimi-log-commit.sh is installed per-operator (`~/.kimi-code/hooks/` + a config.toml `[[hooks]]` block), not copied into projects by install.sh. A project counts as session-logging only when `docs/sessions/status/` or `sessions/status/` exists (`docs/sessions` checked first), and the hook stages only that dir. If `git status --porcelain` shows any path outside it, the hook backs off and never commits.

**Why:** Safety net so unrelated WIP left by the agent or human is never swept into the auto-commit.

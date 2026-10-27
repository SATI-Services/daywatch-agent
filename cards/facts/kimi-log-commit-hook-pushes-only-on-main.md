---
title: kimi-log-commit.sh pushes only when on main branch
tags: [sessions, hooks, safety]
status: verified 2026-10-27 auto
source: [.claude/hooks/kimi-log-commit.sh]
as_of: 28ce500a6 2026-10-26
---
After committing session logs, kimi-log-commit.sh checks git symbolic-ref and pushes to origin only when on `main` branch. Push is best-effort, never fatal.

**Why:** Enforces the repo's commit-to-main-and-push convention; push failures are silent to avoid blocking the SessionEnd event.

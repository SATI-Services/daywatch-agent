---
title: log-commit.sh commits only the log file by explicit pathspec
tags: [sessions, hooks, safety]
status: verified 2026-10-29 auto
source: [.claude/hooks/log-commit.sh]
as_of: 075408cc5 2026-10-28
---
log-commit.sh uses `git add -- <log>` and commits by explicit pathspec; never sweeps
uncommitted files from other agents.

**Why:** Explicit pathspec safety: other agents' uncommitted work cannot be swept into the
commit. Log line survives Track B throwaway worktree → into PR before worktree is removed.

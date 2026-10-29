---
title: log-commit.sh commits its session-log file with --no-verify to skip pre-commit hooks
tags: [sessions, hooks, automation]
status: verified 2026-10-29 auto
source: [.claude/hooks/log-commit.sh]
as_of: 075408cc5 2026-10-28
---
log-commit.sh persists the log line with `git commit -q --no-verify -m "docs(session): log ${sha}" -- "$log"`, so pre-commit hooks do not run for this commit.

**Why:** `--no-verify` keeps pre-commit hooks out of a background automation; the commit is docs-only and commits only its own dated-log file, by explicit pathspec.

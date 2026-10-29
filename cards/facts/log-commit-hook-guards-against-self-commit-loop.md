---
title: log-commit.sh guards against re-triggering on its own commit
tags: [sessions, hooks, safety]
status: verified 2026-10-29 auto
source: [.claude/hooks/log-commit.sh]
as_of: 075408cc5 2026-10-28
---
Hook skips commits that touch only docs/sessions/; guards against self-loop where log-commit
triggers itself on its own "docs(session): log" commit.

**Why:** Prevents infinite re-entry on repeated hook runs; only docs-only commits are safe to
skip.

---
title: log-commit.sh maps git user.name to author slug via sed pattern
tags: [sessions, hooks, author]
status: verified 2026-10-29 auto
source: [.claude/hooks/log-commit.sh]
as_of: 075408cc5 2026-10-28
---
`.claude/hooks/log-commit.sh` resolves the author slug from `git config user.name` with
`sed -E 's/^[a-z]+-//; s/[ -].*//' | tr '[:upper:]' '[:lower:]'`: strip a leading lowercase prefix
and its hyphen, truncate at the first space or hyphen, lowercase. It then targets
`docs/sessions/<author>/` and exits 0 without writing a log line if that directory does not exist.

**Why:** Teams tune that one sed to their git-author → author-slug convention (e.g. "acme-jane-doe" →
"jane", "Jane Doe" → "jane"); an unmapped user's commits are never logged.

---
title: log-commit.sh appends short sha and subject to dated session log
tags: [sessions, hooks, commit]
status: verified 2026-10-29 auto
source: [.claude/hooks/log-commit.sh]
as_of: 075408cc5 2026-10-28
---
Hook (wire as PostToolUse on the Bash tool, or a git post-commit hook) appends `- \`<short-sha>\` <subject>`
to today's dated session log at `docs/sessions/<author>/<date>-*.md` — the newest matching file, else it
creates `<date>-session.md` — then commits only that file by explicit pathspec
(`git commit --no-verify -m "docs(session): log <sha>" -- "$log"`), never `git add -A`.

Skips when the sha is already logged, when the commit only touches `docs/sessions/` (so the hook never
self-logs), and when the git `user.name` maps to no `docs/sessions/<author>/` dir.

**Why:** Session log captures the work without requiring model calls or network; cheap and
deterministic. Committing by explicit pathspec keeps the line from stranding in Track B's throwaway
worktree without sweeping another agent's uncommitted work in.

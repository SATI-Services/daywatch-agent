#!/usr/bin/env bash
# Post-commit hook (wire as PostToolUse on the Bash tool, or a git post-commit hook):
# after a successful `git commit`, append the new short-sha + subject to today's
# dated session log — satisfying "append a one-liner after every commit".
#
# Deterministic + cheap: no model call, no network. It commits ONLY its own dated-log file,
# by explicit pathspec — so it can never sweep another agent's uncommitted work into a commit
# (the original "never git add -A" safety concern), yet the log line still survives on Track B,
# whose throwaway worktree is removed after the PR. A log that is only EDITED and never committed
# evaporates with that worktree — which is the half of the work that most needs a log.
#
# Author folder is derived from git user.name; bails quietly if that author has
# no docs/sessions/<author>/ dir (no-op for unmapped users). Tune the sed mapping
# below to your team's git-author -> author-slug convention.

set -u
cd "${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null)}" || exit 0

# When used as a PostToolUse(Bash) hook, confirm the call was a git commit.
payload=$(cat 2>/dev/null)
if [ -n "$payload" ]; then
  cmd=$(printf '%s' "$payload" | python3 -c 'import json,sys
try: print(json.load(sys.stdin).get("tool_input",{}).get("command",""))
except Exception: print("")' 2>/dev/null)
  case "$cmd" in *"git commit"*) ;; *) exit 0 ;; esac
  case "$cmd" in *"--dry-run"*|*"--help"*) exit 0 ;; esac
fi

# Resolve author slug from git user.name. Adjust the sed to your convention,
# e.g. "acme-jane-doe" -> "jane", "Jane Doe" -> "jane".
who=$(git config user.name 2>/dev/null)
author=$(printf '%s' "$who" | sed -E 's/^[a-z]+-//; s/[ -].*//' | tr '[:upper:]' '[:lower:]')
[ -n "$author" ] || exit 0
logdir="docs/sessions/${author}"
[ -d "$logdir" ] || exit 0

today=$(date +%F)
log=$(ls -t "${logdir}/${today}"-*.md 2>/dev/null | head -1)
if [ -z "$log" ]; then
  log="${logdir}/${today}-session.md"
  printf '# %s — %s session log\n\n_Auto-started; rename to a topic._\n\n## Commits\n\n' "$today" "$author" > "$log"
fi

sha=$(git rev-parse --short HEAD 2>/dev/null); [ -n "$sha" ] || exit 0
grep -q "\`${sha}\`" "$log" 2>/dev/null && exit 0   # already logged

# Skip commits that ONLY touch docs/sessions/ — else session-log commits self-log forever.
# This same guard stops the log commit we make below from re-triggering on its own next run.
changed=$(git diff-tree --no-commit-id --name-only -r HEAD 2>/dev/null)
if [ -n "$changed" ] && ! printf '%s\n' "$changed" | grep -qv '^docs/sessions/'; then exit 0; fi

printf -- '- `%s` %s\n' "$sha" "$(git log -1 --format=%s 2>/dev/null)" >> "$log"

# Persist the line so it can't strand. Commit ONLY this one log file, by explicit pathspec:
# it can never pick up another agent's uncommitted changes (only the path we name is committed),
# and on Track B the line rides along in the worktree branch → into the PR, instead of being
# discarded when the worktree is removed. The commit is docs-only, so the guard above ignores it
# next time. --no-verify keeps pre-commit hooks out of a background automation.
git add -- "$log" 2>/dev/null || exit 0
git commit -q --no-verify -m "docs(session): log ${sha}" -- "$log" 2>/dev/null || true
exit 0

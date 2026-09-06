#!/usr/bin/env bash
# Kimi Code SessionEnd hook — conservative auto-commit of the project's session-log files.
#
# Safety rules (mirrors AGENTS.md): NEVER `git add -A`; stage only files under the session-log
# convention; never commit when ANY unrelated change is present (the agent may have left WIP);
# push only when on `main` (the repo's commit-to-main-and-push convention).
#
# This is a safety net for the human/agent who wrote the log but forgot to commit it — the log
# content itself is written by the model during the session, not by this script.
#
# Per-operator install (~/.kimi-code/hooks/ + config.toml [[hooks]] block) — NOT copied into
# projects by install.sh. Named kimi-* because log-commit.sh in this dir is the *Claude Code*
# PostToolUse hook (different event, different job). See kimi-config.example.toml + README.md.
set -u

input="$(cat 2>/dev/null || true)"
cwd="$(printf '%s' "$input" | sed -n 's/.*"cwd"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -1)"
[ -d "$cwd" ] || exit 0
cd "$cwd" 2>/dev/null || exit 0
[ -d .git ] || exit 0

# session-logging project?
sdir=""
for cand in docs/sessions sessions; do
  [ -d "$cand/status" ] && { sdir="$cand"; break; }
done
[ -n "$sdir" ] || exit 0

# Stage only the session-log dir. Determine changed paths; if any lie outside the sdir, back off.
gitstatus="$(git status --porcelain)"
[ -n "$gitstatus" ] || exit 0
bad=""
while IFS= read -r line; do
  [ -z "$line" ] && continue
  p="${line:3}"                       # strip status markers (2 cols) + space
  case "$p" in
    "$sdir"/*) ;;                     # inside session-log dir: OK
    *) bad="$bad $p" ;;               # anything else: back off
  esac
done <<< "$gitstatus"
[ -z "$bad" ] || exit 0               # unrelated WIP exists -> never commit it

git add "$sdir/" 2>/dev/null || exit 0
git diff --cached --quiet && exit 0   # nothing staged -> done

git -c user.name="$(git config user.name 2>/dev/null || true)" \
    -c user.email="$(git config user.email 2>/dev/null || true)" \
    commit -q -m "docs(sessions): auto-commit session log update (kimi SessionEnd hook)" \
    || true

# push only on main (repo default-main convention); best-effort, never fatal
br="$(git symbolic-ref --short HEAD 2>/dev/null || true)"
if [ "$br" = "main" ]; then
  git push -q origin main 2>/dev/null || true
fi
exit 0

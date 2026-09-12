#!/usr/bin/env bash
# Session-start hook: emit a COMPACT briefing (git state + priorities + in-flight
# headlines + notes inbox + open PRs + daily-E2E status) and POINT to the bulk
# reference material rather than dumping it.
#
# WHY compact: harnesses truncate oversized hook output to a tiny preview, so a
# briefing that cat's full docs never reaches the model. Headlines + paths only;
# the agent reads the pointed-to files on demand.
#
# Wire into the harness as a SessionStart hook. Set REPO + PROJECT_DIR for your project.

set -u
PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null)}"
S="$PROJECT_DIR/docs/sessions"
REPO="SATI-Services/daywatch-agent"        # e.g. MyOrg/MyProject — for gh pr/run queries; leave blank to skip
E2E_WF="tests.yml"   # scheduled E2E workflow filename; leave blank/placeholder to skip
MAIN_BRANCH="main"     # your trunk branch (main / master); install.sh --branch sets this
G() { git -C "$PROJECT_DIR" "$@" 2>/dev/null; }
# "still a placeholder?" — true when the value still contains {{...}} (installer left it unset).
# Guarding on this (not on the literal slug) survives substitution: a real value has no braces.
is_set() { [ -n "$1" ] && [ "${1#*\{\{}" = "$1" ]; }
# Trunk defaults to `main` if the placeholder was never filled, so the hook still works untouched.
is_set "$MAIN_BRANCH" || MAIN_BRANCH="main"

echo '======================================================================'
echo 'FIRST ACTION: open your first reply with a "## Session Briefing" that'
echo 'summarises the data below (you/branch/uncommitted, priorities, blockers,'
echo 'open PRs, latest E2E). Briefing first, THEN the user request.'
echo '======================================================================'
echo
echo '=== SESSION BRIEFING ==='
echo "You: $(git config user.name 2>/dev/null || echo unknown)"
echo "Branch: $(G rev-parse --abbrev-ref HEAD)"
echo "Uncommitted files: $(G status --porcelain | wc -l | tr -d ' ')"
echo "vs origin/$MAIN_BRANCH behind/ahead (last-known, no fetch): $(G rev-list --left-right --count "origin/$MAIN_BRANCH...HEAD")"
echo "Notes in inbox: $(ls "$S"/notes/*.md 2>/dev/null | wc -l | tr -d ' ')"

echo
echo '=== Priorities / blockers / transients (docs/sessions/SHARED.md) ==='
awk '/^## / { want = ($0 ~ /^## Current priorities/ || $0 ~ /^## Shared blockers/ || $0 ~ /^## Known transients/) } want { print }' \
  "$S/SHARED.md" 2>/dev/null || echo '(SHARED.md missing)'

echo
echo '=== In-flight + next-up headlines (docs/sessions/status/<author>.md — full detail there) ==='
for f in "$S"/status/*.md; do
  [ -e "$f" ] || continue
  case "$(basename "$f")" in _TEMPLATE.md) continue;; esac
  author=$(basename "$f" .md)
  # First ≤2 bullets under "## In flight" (what they're doing now) and ≤2 under
  # "## Next up" (top of their backlog). Match both "- " and "1." list styles.
  inflight=$(awk '/^## In flight/{i=1;next} /^## /{i=0} i&&/^([-*]|[0-9]+\.) /{print;c++} c>=2{exit}' "$f")
  nextup=$(awk '/^## Next up/{i=1;next} /^## /{i=0} i&&/^([-*]|[0-9]+\.) /{print;c++} c>=2{exit}' "$f")
  if [ -n "$inflight" ] || [ -n "$nextup" ]; then
    echo "• ${author}:"
    [ -n "$inflight" ] && { echo "  in flight:"; echo "$inflight" | sed 's/^/    /'; }
    [ -n "$nextup" ]   && { echo "  next up:";   echo "$nextup"   | sed 's/^/    /'; }
  fi
done

echo
echo '=== Notes inbox (read if addressed to you, then git mv to notes/archive/) ==='
n=0
for f in "$S"/notes/*.md; do
  [ -e "$f" ] || continue
  n=$((n+1)); echo "- ${f#"$PROJECT_DIR"/} — $(grep -m1 -E '^# ' "$f" | sed -E 's/^# //')"
done
[ "$n" -eq 0 ] && echo '(inbox empty)'

if is_set "$REPO"; then
  echo
  echo '=== Open PRs (Track B in flight) ==='
  prs=$(gh pr list --repo "$REPO" --limit 20 --json number,headRefName,title,author \
    --jq '.[] | "#\(.number) [\(.headRefName)] \(.title) — @\(.author.login)"' 2>/dev/null)
  if [ -n "$prs" ]; then echo "$prs"; else echo '(none open, or gh unavailable)'; fi

  echo
  echo '=== Daily E2E — recent scheduled runs (on "failure": surface + ASK, do not auto-fix) ==='
  if is_set "$E2E_WF"; then
    e2e=$(gh run list --repo "$REPO" --workflow="$E2E_WF" --limit 30 \
      --json conclusion,displayTitle,databaseId,event \
      --jq '[.[] | select(.event=="schedule")] | .[:6][] | "\(.conclusion) · \(.displayTitle) · run \(.databaseId)"' 2>/dev/null)
    if [ -n "$e2e" ]; then
      echo "$e2e"
      # Stash the latest run line for session-tick.sh to render as ✅/❌.
      printf '%s' "$e2e" | head -1 > "/tmp/$(basename "$PROJECT_DIR")-e2e.flag" 2>/dev/null
    else
      echo '(no scheduled runs found, or gh unavailable)'
      rm -f "/tmp/$(basename "$PROJECT_DIR")-e2e.flag" 2>/dev/null
    fi
  else
    echo '(no scheduled E2E workflow configured yet — set E2E_WF once one exists)'
    rm -f "/tmp/$(basename "$PROJECT_DIR")-e2e.flag" 2>/dev/null
  fi
fi

echo
# ---- verified card index (compact project memory) -------------------------
# The board's cut+mark loop commits a repo's verified facts + open todos into
# <repo>/cards/ (facts/, todos/, _INDEX.md). Real indexes run 50-140 KB, so
# this surfaces the COUNTS and a few verified titles and points at the file --
# it never dumps the index. Two layouts: cards/_INDEX.md, and cards/*/_INDEX.md.
echo
echo '=== Verified cards (compact project memory; read the file on demand) ==='
cards_found=0
for idx in "$PROJECT_DIR"/cards/_INDEX.md "$PROJECT_DIR"/cards/*/_INDEX.md; do
  [ -f "$idx" ] || continue
  cards_found=$((cards_found + 1))
  rel="${idx#"$PROJECT_DIR"/}"
  counts=$(grep -m1 -E '^## Facts' "$idx" 2>/dev/null | sed -E 's/^## Facts[^0-9]*//')
  ver=$(grep -m1 -E '^Status:' "$idx" 2>/dev/null | grep -oE 'verified [0-9]+' | head -1)
  echo "- $rel -- ${counts:-?} facts, ${ver:-0 verified}:"
  grep -E '^\| \[[^]]+\]\(facts/.*\| verified ' "$idx" 2>/dev/null | head -3 \
    | awk -F'|' '{ t=$3; gsub(/^[[:space:]]+|[[:space:]]+$/, "", t); print "    - " substr(t, 1, 100) }'
done
if [ "$cards_found" -eq 0 ]; then
  echo '(no cards/ deck in this checkout -- nothing has been cut for this repo yet)'
fi
echo '=== Pointers (read on demand) ==='
echo "- Operating manual: docs/WORKFLOW.md"
echo "- Full shared state: docs/sessions/SHARED.md   Per-author WIP: docs/sessions/status/*.md"
# shellcheck disable=SC2010,SC2012  # `ls -t` is the portable mtime sort (BSD+GNU find diverge on -printf); dated-log filenames are team-slugged
latest=$(ls -t "$S"/*/2*.md 2>/dev/null | grep -vE '/(archive|notes)/' | head -1)
echo "- Latest dated log: ${latest#"$PROJECT_DIR"/}"

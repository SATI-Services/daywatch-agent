#!/usr/bin/env bash
# Companion to session-briefing.sh. Emits the visible SessionStart systemMessage.
#
# Line 1 confirms the briefing hook actually fired (if the user never sees this,
# the hook is broken or its output was truncated). Line 2 reflects the latest
# scheduled daily-E2E run as a ✅/❌ tick, read from a flag the briefing hook
# stashed in /tmp (so we don't pay a second `gh` round-trip here). Line 3 (optional)
# nudges you when your OWN status file is over the length cap — a bloated status
# file taxes every session that loads it. See docs/WORKFLOW.md → Session management.
#
# To use the E2E tick, have session-briefing.sh write its most-recent run line to
# the flag path below (one line, "<conclusion> · <title> · run <id>").

set -u
PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null)}"
flag="/tmp/$(basename "${PROJECT_DIR:-project}")-e2e.flag"
line1='✅ SessionStart briefing hook fired'

if [ -s "$flag" ]; then
  run=$(cat "$flag")
  case "$run" in
    success*) line2="✅ Daily E2E: latest run green — ${run}" ;;
    failure*) line2="❌ Daily E2E: latest run FAILED — ${run} (ask before triaging)" ;;
    *)        line2="⚠️ Daily E2E: latest run ${run}" ;;
  esac
else
  line2='⚠️ Daily E2E: status unavailable (gh not authed, or no flag written)'
fi

# Line 3: status-file length nudge. Derive the author slug by matching this
# machine's git identity against the ACTUAL status files present (substring match,
# so "sts-ryan-holton" / "Ryan Holton" both resolve to ryan.md) — no hardcoded
# roster, so it travels to any project. Detection only: we never edit the file
# (write-partition — only the author prunes it). Cap kept in sync with WORKFLOW.md.
cap=80
line3=''
statusdir="$PROJECT_DIR/docs/sessions/status"
gitname=$(git -C "$PROJECT_DIR" config user.name 2>/dev/null | tr '[:upper:]' '[:lower:]')
if [ -n "$gitname" ] && [ -d "$statusdir" ]; then
  for sf in "$statusdir"/*.md; do
    [ -e "$sf" ] || continue
    slug=$(basename "$sf" .md)
    case "$slug" in _TEMPLATE) continue;; esac
    case "$gitname" in *"$slug"*)
      n=$(wc -l < "$sf" 2>/dev/null | tr -d ' ')
      if [ -n "$n" ] && [ "$n" -gt "$cap" ]; then
        line3="⚠️ status/${slug}.md is ${n} lines (target ≤${cap}) — trim ## Shipped to the recent ~8-item ticker + move merged items out of ## In flight; the dated logs hold the history"
      fi
      break;;
    esac
  done
fi

msg="${line1}
${line2}"
[ -n "$line3" ] && msg="${msg}
${line3}"
printf '%s' "$msg" \
  | python3 -c 'import json,sys; print(json.dumps({"systemMessage": sys.stdin.read()}))' 2>/dev/null \
  || printf '{"systemMessage":"%s"}' "$line1"

#!/usr/bin/env bash
# Kimi Code UserPromptSubmit hook — generic, project-agnostic session-logging reminder.
#
# The AID-harness convention: whenever a project carries the shared-memory PM system
# (sessions/status or docs/sessions/status), every agent keeps its dated session log
# (sessions/<YYYY-MM-DD>-*.md under the operator's name) current as it works and commits+pushes
# it before stopping. This hook emits a short nudge to the model's context on each user prompt so
# the agent logs without being reminded manually.
#
# Detects the convention per-project (no per-project config needed) and throttles so it is not
# emitted every single turn. The text is appended to the model context; exit 0 = allow.
#
# Per-operator install (~/.kimi-code/hooks/ + config.toml [[hooks]] block) — NOT copied into
# projects by install.sh. Named kimi-* so it can't be confused with the Claude Code hooks here.
# See kimi-config.example.toml + README.md (Kimi section) in this dir.
set -u

input="$(cat 2>/dev/null || true)"
cwd="$(printf '%s' "$input" | sed -n 's/.*"cwd"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -1)"
[ -d "$cwd" ] || cwd="$PWD"

# session-logging project? (AID harness: sessions/status or docs/sessions/status)
sdir=""
for cand in "$cwd/docs/sessions" "$cwd/sessions"; do
  [ -d "$cand/status" ] && { sdir="$cand"; break; }
done
[ -n "$sdir" ] || exit 0

# throttle: at most once every 15 min per project
state="${TMPDIR:-/tmp}/kimi_nudge_$(printf '%s' "$cwd" | tr '/\\:' '__')"
if [ -f "$state" ]; then
  age=$(( $(date +%s) - $(stat -c %Y "$state" 2>/dev/null || echo 0) ))
  [ "${age:-0}" -lt 900 ] && exit 0
fi
touch "$state"

# operator = the git identity (sessions are filed under the person, not the agent)
op="$(git -C "$cwd" config user.name 2>/dev/null || true)"
[ -z "$op" ] && op="${USER:-operator}"   # ${USER:-...}: bare $USER crashes under set -u when unset
today="$(date +%Y-%m-%d)"

cat <<EOF
[session-log] This is an AID-harness session-logging project. Keep your dated log
sessions/${today}-*.md current as you work (header author "${op}", stamp "**Agent:** kimi"
inside every file; update only your own section in sessions/status/*.md). Commit and push it
before you stop, per the project's AGENTS.md — skipping this makes this session invisible to the
next one. (You are being nudged by a Kimi Code UserPromptSubmit hook, not asked by the operator.)
EOF
exit 0

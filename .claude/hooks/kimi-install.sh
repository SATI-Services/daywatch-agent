#!/usr/bin/env bash
# kimi-install.sh — per-operator installer for the AID session-logging hooks (Kimi Code CLI).
#
# Run by an agent at session start (the project AGENTS.md bootstrap says when) or by hand.
# Idempotent: installs what's missing, updates what drifted, prints one compact status line.
# Safe by default: backs up config.toml before modifying it, never removes or rewrites an
# operator's other [[hooks]] blocks, and exits 0 on every normal path so it can never block a
# session.
#
#   bash kimi-install.sh           # install/update, print status
#   bash kimi-install.sh --check   # report only; exit 0 = current, 1 = missing/stale
#
# Layout it manages (per operator, NOT per project — one install covers every AID project,
# because the hooks detect the sessions/status or docs/sessions/status convention per project):
#   ~/.kimi-code/hooks/session-log-nudge.sh   <- UserPromptSubmit nudge
#   ~/.kimi-code/hooks/log-commit.sh          <- SessionEnd safety-net commit
#   ~/.kimi-code/config.toml                  <- the two [[hooks]] blocks (created if absent)
#
# Source scripts sit next to THIS script as kimi-*.sh — the prefix exists only to disambiguate
# inside a project's .claude/hooks/ from the Claude Code hooks; installed names drop the prefix
# (and any v1.3-era prefixed paths in config.toml are migrated). session-briefing.sh is NOT
# managed here: it is configured per project (REPO etc.), so a per-operator global copy would
# answer gh queries for the wrong repo — wire it by hand if you want it.
set -u

CHECK_ONLY=0
[ "${1:-}" = "--check" ] && CHECK_ONLY=1

# --- resolve this script's dir (source scripts live beside it) ---
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do
  D="$(cd -P "$(dirname "$SOURCE")" && pwd)"; SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$D/$SOURCE"
done
HERE="$(cd -P "$(dirname "$SOURCE")" && pwd)"

KDIR="$HOME/.kimi-code"
KHOOKS="$KDIR/hooks"
CONF="$KDIR/config.toml"
CHANGED=0   # set to 1 when anything was installed/updated (normal mode)
MISSING=0   # set to 1 when anything is missing/stale (check mode)

# --- not a Kimi Code machine? nothing to do (still exit 0) ---
if [ ! -d "$KDIR" ] && ! command -v kimi >/dev/null 2>&1; then
  echo "kimi-hooks: skip (no ~/.kimi-code and no kimi on PATH)"
  exit 0
fi

# --- scripts: copy when missing or drifted ---
install_one() { # $1 = source name (prefixed), $2 = installed name (unprefixed)
  local src="$HERE/$1" dst="$KHOOKS/$2" existed=0
  if [ ! -f "$src" ]; then echo "kimi-hooks: WARN source script missing beside installer: $1"; return; fi
  [ -f "$dst" ] && existed=1
  if [ "$existed" -eq 1 ] && cmp -s "$src" "$dst"; then return 0; fi   # current
  if [ "$CHECK_ONLY" -eq 1 ]; then
    if [ "$existed" -eq 1 ]; then echo "kimi-hooks: stale   $2 (update available)";
    else echo "kimi-hooks: missing $2"; fi
    MISSING=1; return 0
  fi
  mkdir -p "$KHOOKS"
  cp "$src" "$dst" && chmod +x "$dst"
  if [ "$existed" -eq 1 ]; then echo "kimi-hooks: updated   $2"; else echo "kimi-hooks: installed $2"; fi
  CHANGED=1
}

# --- config.toml: is a [[hooks]] block wiring <event> to <script basename>? ---
# (substring match also covers v1.3-era prefixed command paths)
wired() { # $1 = event, $2 = script basename
  [ -f "$CONF" ] || return 1
  awk -v ev="$1" -v script="$2" '
    /^\[\[hooks\]\]/ { inev=0 }
    /^[[:space:]]*event[[:space:]]*=/ { inev = ($0 ~ "\"" ev "\"") ? 1 : 0 }
    /^[[:space:]]*command[[:space:]]*=/ && inev && ($0 ~ script) { found=1 }
    END { exit (found ? 0 : 1) }
  ' "$CONF"
}

# --- is <event> wired to some OTHER command (operator's own hook)? -> never clobber ---
event_taken() { # $1 = event, $2 = script basename
  [ -f "$CONF" ] || return 1
  awk -v ev="$1" -v script="$2" '
    /^\[\[hooks\]\]/ { inev=0 }
    /^[[:space:]]*event[[:space:]]*=/ { inev = ($0 ~ "\"" ev "\"") ? 1 : 0 }
    /^[[:space:]]*command[[:space:]]*=/ && inev && !($0 ~ script) { other=1 }
    END { exit (other ? 0 : 1) }
  ' "$CONF"
}

BACKED_UP=0
backup_conf() {
  [ "$BACKED_UP" -eq 0 ] && [ -f "$CONF" ] && { cp "$CONF" "$CONF.bak-$(date +%Y%m%d-%H%M%S)"; }
  BACKED_UP=1
}

ensure_block() { # $1 = event, $2 = installed script basename, $3 = timeout
  if wired "$1" "$2"; then return 0; fi
  if event_taken "$1" "$2"; then
    echo "kimi-hooks: WARN $1 already wired to a different command in $CONF — left untouched; merge manually"
    return 0
  fi
  if [ "$CHECK_ONLY" -eq 1 ]; then echo "kimi-hooks: missing [[hooks]] block for $1"; MISSING=1; return 0; fi
  backup_conf
  mkdir -p "$KDIR"
  printf '\n[[hooks]]\nevent = "%s"\ncommand = "bash ~/.kimi-code/hooks/%s"\ntimeout = %s\n' \
    "$1" "$2" "$3" >> "$CONF"
  echo "kimi-hooks: wired    $1 -> $2 in config.toml"
  CHANGED=1
}

# v1.3 briefly documented prefixed install paths — rewrite them to unprefixed (files install
# unprefixed; a prefixed path would point at nothing).
migrate_prefixed() {
  [ -f "$CONF" ] || return 0
  grep -q 'kimi-session-log-nudge\.sh\|kimi-log-commit\.sh' "$CONF" || return 0
  if [ "$CHECK_ONLY" -eq 1 ]; then echo "kimi-hooks: stale prefixed paths in config.toml (will migrate)"; MISSING=1; return 0; fi
  backup_conf
  sed -i 's/kimi-session-log-nudge\.sh/session-log-nudge.sh/g; s/kimi-log-commit\.sh/log-commit.sh/g' "$CONF"
  echo "kimi-hooks: migrated prefixed hook paths in config.toml"
  CHANGED=1
}

if [ "$CHECK_ONLY" -eq 0 ] && [ ! -f "$CONF" ]; then
  mkdir -p "$KDIR"
  printf '# Kimi Code CLI configuration.\n# AID session-logging hooks managed by kimi-install.sh are appended below.\n' > "$CONF"
  CHANGED=1
fi

migrate_prefixed
install_one kimi-session-log-nudge.sh session-log-nudge.sh
install_one kimi-log-commit.sh       log-commit.sh
ensure_block UserPromptSubmit session-log-nudge.sh 5
ensure_block SessionEnd       log-commit.sh 30

if [ "$CHECK_ONLY" -eq 1 ]; then
  if [ "$MISSING" -eq 0 ]; then echo "kimi-hooks: current (~/.kimi-code)"; exit 0;
  else exit 1; fi
fi
if [ "$CHANGED" -eq 1 ]; then
  echo "kimi-hooks: READY — installed/updated in ~/.kimi-code. Hooks load at session start:"
  echo "kimi-hooks: start a FRESH Kimi Code session to activate them."
else
  echo "kimi-hooks: current (~/.kimi-code)"
fi
exit 0

---
title: kimi-install.sh manages session-log-nudge.sh and log-commit.sh per operator, not per project
tags: [session-logging, tooling, hooks]
status: verified 2026-10-26 auto
source: [.claude/hooks/kimi-install.sh]
as_of: d4813a7e6 2026-10-25
---
kimi-install.sh manages two hooks in `~/.kimi-code/hooks/`, per operator and NOT per project — one
install covers every AID project, because the hooks detect each project's `sessions/status` or
`docs/sessions/status` convention:

- `session-log-nudge.sh`: wired to `UserPromptSubmit`, timeout 5s
- `log-commit.sh`: wired to `SessionEnd`, timeout 30s

Both are copied from the kimi-prefixed source scripts beside the installer in `.claude/hooks/` and
installed unprefixed; v1.3-era prefixed paths in `~/.kimi-code/config.toml` are migrated to
unprefixed. Hooks load at session start — start a FRESH Kimi Code session to activate them.
`session-briefing.sh` is NOT managed here: it is configured per project (REPO etc.), so a
per-operator global copy would answer gh queries for the wrong repo.

**Why:** Central per-operator hook management; the `kimi-` prefix exists only to disambiguate the
source scripts from the Claude Code hooks inside a project's `.claude/hooks/`.

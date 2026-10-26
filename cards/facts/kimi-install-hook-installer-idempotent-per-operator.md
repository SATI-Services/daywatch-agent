---
title: kimi-install.sh is the per-operator idempotent hook installer
tags: [session-logging, tooling, hooks]
status: verified 2026-10-26 auto
source: [.claude/hooks/kimi-install.sh]
as_of: bd1f2a930 2026-10-26
---
`kimi-install.sh` installs/updates the AID session-logging hooks into `~/.kimi-code` — per operator, not per project: one install covers every AID project, because the hooks detect the `sessions/status` or `docs/sessions/status` convention per project. An agent runs it at session start when the project AGENTS.md bootstrap says so, or by hand. Idempotent: it installs what's missing, updates what drifted, backs up `config.toml` before modifying it, and exits 0 on every normal path so it can never block a session. On a machine with no `~/.kimi-code` and no `kimi` on PATH it skips and still exits 0.

It manages two scripts and their `[[hooks]]` blocks: `session-log-nudge.sh` (event `UserPromptSubmit`, timeout 5) and `log-commit.sh` (event `SessionEnd`, timeout 30). It never removes or rewrites the operator's *other* `[[hooks]]` blocks; if an event is already wired to a different command it leaves that block untouched and prints `WARN … merge manually` — a WARN means the hook is still unwired and needs merging by hand. Installed scripts drop the `kimi-` prefix; v1.3-era prefixed paths in `config.toml` are migrated. `session-briefing.sh` is NOT managed here — it is configured per project (REPO etc.), so a per-operator global copy would answer gh queries for the wrong repo; wire it by hand. Hooks load at session start — start a fresh Kimi Code session to activate them.

**Why:** Centralizing per operator avoids per-project duplication and collision; the session/status or docs/sessions/status convention lets one install serve every AID project.

**Runbook:**


bash .claude/hooks/kimi-install.sh           # install/update, print status
bash .claude/hooks/kimi-install.sh --check   # report only; exit 0 = current, 1 = missing/stale

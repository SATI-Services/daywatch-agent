---
title: kimi-install.sh is per-operator idempotent hook installer
tags: [session-logging, tooling, hooks]
status: verified 2026-10-26 auto
source: [.claude/hooks/kimi-install.sh]
as_of: d4813a7e6 2026-10-25
---
kimi-install.sh installs/updates the AID session-logging hooks into `~/.kimi-code` — per operator, not per project: one install covers every AID project, because the hooks detect the `sessions/status` or `docs/sessions/status` convention per project. Idempotent: an agent runs it at session start or by hand; it installs what's missing, updates what drifted, backs up `config.toml` before modifying it, and exits 0 on every normal path so it can never block a session.

It never removes or rewrites the operator's *other* `[[hooks]]` blocks. If an event (`UserPromptSubmit`, `SessionEnd`) is already wired to a different command, it leaves that block untouched, prints `WARN … merge manually`, and installs nothing for that event — a WARN means the hook is still unwired and needs merging by hand. Installed scripts drop the `kimi-` prefix; v1.3-era prefixed paths in `config.toml` are migrated. `session-briefing.sh` is NOT managed here (it is configured per project), so wire it by hand. Hooks load at session start — start a fresh Kimi Code session to activate them.

**Why:** Operator's hooks cover every AID project via the session/status or docs/sessions/status convention; centralizing per operator avoids per-project duplication and collision.

**Runbook:**

```
bash .claude/hooks/kimi-install.sh           # install/update, print status
bash .claude/hooks/kimi-install.sh --check   # report only; exit 0 = current, 1 = missing/stale
```

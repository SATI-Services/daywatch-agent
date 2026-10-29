---
title: kimi-session-log-nudge appends [session-log] instructions to the model's context
tags: [sessions, hooks, kimi]
status: verified 2026-10-29 auto
source: [.claude/hooks/kimi-session-log-nudge.sh]
as_of: 6c9699294 2026-10-29
---
In a project where `docs/sessions/status` or `sessions/status` is a directory, the Kimi
`UserPromptSubmit` hook appends a `[session-log]` block to the model's context — throttled to at
most once every 900s (15 min) per project, tracked by a state file in `${TMPDIR:-/tmp}`, so not on
every turn. It tells the agent to:

- keep `sessions/<YYYY-MM-DD>-*.md` current as it works, header author set to the operator
  (git `user.name`, falling back to `$USER`, then `operator`) and `**Agent:** kimi` stamped
  inside every file;
- update only its own section in `sessions/status/*.md`;
- commit and push the log before stopping, per the project's AGENTS.md.

The block says it comes from a Kimi Code `UserPromptSubmit` hook, not from the operator. The
script is installed per-operator (`~/.kimi-code/hooks/` plus a `config.toml` `[[hooks]]` block)
and is NOT copied into projects by `install.sh`.

**Why:** An on-context reminder makes session logging frictionless; the block warns that skipping
it "makes this session invisible to the next one."

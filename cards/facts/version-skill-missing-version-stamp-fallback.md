---
title: Missing VERSION stamp means the project predates versioning — fall back to the changelog
tags: [harness, version, fallback]
status: verified 2026-10-26 auto
source: [.claude/skills/version/SKILL.md]
as_of: bd1f2a930 2026-10-26
---
If `.claude/harness/VERSION` is missing, the project predates versioning (or was set up by hand). Say so, and tell the user they can stamp it by re-running the harness `install.sh` or adding the file manually; then fall back to reporting from `.claude/harness/CHANGELOG.md`'s newest entry as the assumed version.

**Why:** An absent stamp is an unstamped install, not "no version" — the changelog's newest entry is the best local evidence of what is applied.

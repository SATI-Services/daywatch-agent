---
title: Missing VERSION stamp: say the project predates versioning, assume the CHANGELOG's newest version
tags: [harness, version, fallback]
status: verified 2026-10-26 auto
source: [.claude/skills/version/SKILL.md]
as_of: bd1f2a930 2026-10-26
---
If `.claude/harness/VERSION` is missing, the project predates versioning (or was set up by hand). Say so, and tell the user they can stamp it by re-running the harness `install.sh` or by adding the file manually. Then fall back to reporting from the newest entry of `.claude/harness/CHANGELOG.md` as the assumed version.

**Why:** `/version` still has to answer "what am I on?" when the stamp was never written.

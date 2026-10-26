---
title: VERSION stamp has version, applied date, source repo, source sha, optional notes
tags: [harness, version]
status: verified 2026-10-26 auto
source: [.claude/skills/version/SKILL.md]
as_of: d4813a7e6 2026-10-25
---
.claude/harness/VERSION is a key/value stamp:
- version: <N> (e.g., 1.0)
- applied: <YYYY-MM-DD>
- source: <ORG/REPO of the harness>
- source_commit: <short sha the applied content came from>
- notes: <optional, e.g., "right-sized: no live-verify/parity-audit">

If missing, the project predates versioning (or was set up by hand). Say so and tell the user they can stamp it by re-running the harness `install.sh` or adding the file manually; then fall back to reporting the changelog's newest entry as the assumed version.

**Why:** Tracks provenance and timestamp of the harness applied to the project; `source` enables the version-freshness check against the canonical repo.

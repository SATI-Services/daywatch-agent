---
title: VERSION stamp has version, applied date, source repo, source sha, optional notes
tags: [harness, version]
status: verified 2026-10-26 auto
source: [.claude/skills/version/SKILL.md]
as_of: bd1f2a930 2026-10-26
---
`.claude/harness/VERSION` is a key/value stamp:
- version: <N> (major.minor, rendered with a leading `v`, e.g. `v1.0`)
- applied: <YYYY-MM-DD>
- source: <ORG/REPO of the harness>
- source_commit: <short sha the applied content came from>
- notes: <optional, e.g. "right-sized: no live-verify/parity-audit">

**Why:** Tracks the provenance and timestamp of the harness applied to this project, enabling the version-freshness check.

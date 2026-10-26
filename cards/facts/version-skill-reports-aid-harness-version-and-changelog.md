---
title: /version reports AID harness version and changelog, read-only
tags: [harness, tooling, diagnostics]
status: verified 2026-10-26 auto
source: [.claude/skills/version/SKILL.md]
as_of: d4813a7e6 2026-10-25
---
`/version` is a read-only report — it never upgrades anything. It reads the project's `.claude/harness/VERSION`
stamp and `.claude/harness/CHANGELOG.md` (a copy of the harness changelog captured at apply time) and renders a
`/stats`-style block: `This project v<N.M> (applied <date> · from <source>@<sha>)`, `Latest v<X.Y>` (✅ up to
date | ⬆ upgrade available | — check unavailable), then the changelog headlines and bullets — current version in
full, earlier versions condensed. Versions are `major.minor`, rendered with a leading `v`.

The freshness comparison runs only if `gh` is authenticated; a failed network/gh call must never break the core
report — report the local version and skip the comparison. If `.claude/harness/VERSION` is missing, say the
project predates versioning (it can be stamped by re-running `install.sh`) and fall back to the changelog's
newest entry as the assumed version. If the changelog is absent, read `docs/WORKFLOW.md`'s header and say the
changelog wasn't shipped.

**Why:** The user needs to know what harness they are on and what features it has, without being handed commands
to run.

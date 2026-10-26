---
title: /version reports AID harness version and changelog, read-only
tags: [harness, tooling, diagnostics]
status: verified 2026-10-26 auto
source: [.claude/skills/version/SKILL.md]
as_of: bd1f2a930 2026-10-26
---
`/version` is a read-only report — it never upgrades anything. It reads the project's `.claude/harness/VERSION` stamp and `.claude/harness/CHANGELOG.md` (a copy of the harness changelog captured at apply time) and renders a `/stats`-style block: `This project v<N.M> (applied <date> · from <source>@<sha>)`, `Latest v<X.Y>`, then the changelog headlines and bullets. Versions are `major.minor`, rendered with a leading `v`.

The optional freshness check runs only if `gh` is authenticated (`gh api repos/<source>/contents/VERSION`), and reports one of three states:
- **up to date** — canonical equals local.
- **behind — vX available** — canonical is higher; mention `install.sh` re-applies and re-stamps.
- **ahead / unknown** — local is higher, or `gh` is unavailable; report the local version and skip the comparison.

A failed network/gh call must never break the core report. If `.claude/harness/VERSION` is missing, say the project predates versioning (it can be stamped by re-running `install.sh`) and fall back to the changelog's newest entry as the assumed version. If the changelog is absent, read `docs/WORKFLOW.md`'s header and say the changelog wasn't shipped with this install.

**Why:** The user needs to know what harness they are on and what features it has, without being handed commands to run.

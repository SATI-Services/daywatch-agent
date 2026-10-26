---
title: /version freshness check runs gh API only when authenticated and never breaks the core report
tags: [harness, tooling, diagnostics]
status: verified 2026-10-26 auto
source: [.claude/skills/version/SKILL.md]
as_of: d4813a7e6 2026-10-25
---
The `/version` freshness check is optional — run it only if `gh` is authenticated. Read `source` from the project's stamp (`.claude/harness/VERSION`), then fetch the canonical latest version from the harness repo:

```bash
gh api "repos/<source>/contents/VERSION" --jq '.content' 2>/dev/null | base64 --decode
```

Compare it to the project's `version` and report one of: **up to date** (equal), **behind — vX available** (canonical is higher; mention `install.sh` re-applies + re-stamps), or **ahead / unknown** (local is higher, or gh unavailable — just report the local version and skip the comparison silently on failure). Never let a failed network/gh call break the core report.

**Why:** The core report needs no network; the comparison only tells the user whether they are behind and degrades gracefully when gh or the network is unavailable.

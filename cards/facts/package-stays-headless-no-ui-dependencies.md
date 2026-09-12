---
title: Package has no rendered surface (no UI, no checked-in headless driver)
tags: [package, dependencies, design]
status: verified 2026-09-12 auto
source: [docs/sessions/SHARED.md]
as_of: f10ffdf64 2026-09-12
---
The package has no rendered surface and no checked-in headless driver, so `.claude/skills/live-verify` was deleted as N/A at AID adoption on 2026-09-06.

**Why:** The real-surface check for this package is the "hostile host" suite plus `daywatch:status`.

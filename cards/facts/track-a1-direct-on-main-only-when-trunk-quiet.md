---
title: Track A1 commits direct on main only when the trunk is quiet and you push within seconds
tags: [git, workflow, track-a1]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: c67a73d16 2026-11-06
---
**A1 — direct-on-main:** legal, but only when `main` is quiet and you will push within seconds.

"Never commit on local `main`" is the A2 shorthand, not an absolute — an adapter must not turn it into a rule that forbids A1.

**Why:** An un-pushed commit on local `main` can only diverge; pushing within seconds keeps `main` fast-forward-only.

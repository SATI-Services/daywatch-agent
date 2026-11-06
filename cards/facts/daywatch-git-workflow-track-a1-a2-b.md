---
title: Two shipping tracks — A (push-to-main, default) and B (branch + PR)
tags: [git, workflow, track]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: c67a73d16 2026-11-06
---
Both tracks are first-class; pick by size and risk.

- **Track A — push-to-main (default; fast iteration):** `edit → commit on a task branch → fast-forward to main → push → CI`. Use for 1–3 commit, coherent, reversible changes. Litmus test: "comfortable leaving this on prod overnight?"
- **Track B — branch + PR:** topic branch → push → `gh pr create`. Use for 5+ commits, schema migrations, structural refactors, and anything touching the critical core.

**Why:** Track A keeps the pace; Track B keeps the critical core safe.

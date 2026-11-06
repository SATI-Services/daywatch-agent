---
title: Track B — merge your own PR with gh pr merge --squash, never GitHub auto-merge
tags: [git, workflow, track-b]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: c67a73d16 2026-11-06
---
Topic branch → push → `gh pr create`; the PR description is the review summary. Then **merge your own PR** with `gh pr merge --squash` — no permission, and nothing waits on a human; the PR is the documentation left in the repo.

**Do not use GitHub's auto-merge** — the agent merges.

**Why:** Operator standard for this project. "Never commit to `main`" and "everything via PR" are both wrong absolutes.

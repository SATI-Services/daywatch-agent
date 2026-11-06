---
title: Commit and push without asking; only force-push, amend, reset --hard, prod revert need a check
tags: [git, workflow, authorisation]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: c67a73d16 2026-11-06
---
Committing and pushing is the operator's standing, pre-authorised policy on every track (A1/A2) — do it without asking, and never end a turn offering to.

The only git operations that need a check first: **force-push / amending pushed commits / `reset --hard` / reverting something on prod.**

**Why:** A model that stops to ask for a normal commit contradicts the standing authorisation.

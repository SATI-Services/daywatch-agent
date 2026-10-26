---
title: session-docs skill is the runnable version of the sessions README contract
tags: [session-logs, skills, harness]
status: verified 2026-10-26 auto
source: [docs/sessions/README.md]
as_of: 043dd516d 2026-10-26
---
The sessions discipline is also an executable skill: the exact paths, templates, and the Track-A2 command sequence for publishing docs to the trunk live in the `session-docs` process skill at `.claude/skills/session-docs/`. `docs/sessions/README.md` is the contract; the skill is the runnable version of it.

**Why:** Encoding the moves as a skill lets a lighter model perform the same session-docs discipline without re-deriving it from the README.

---
title: Skill Bench with-skill pass costs ~1 Haiku per eval + 1 Sonnet grading call; --baseline doubles
tags: [process, skills, testing]
status: verified 2026-10-30 auto
source: [.claude/skills/skill-bench/SKILL.md]
as_of: 993c90ef1 2026-10-30
---
A full with-skill run is one Haiku agent session per eval plus a Sonnet grading call per eval. Running `--baseline` (skill-stripped comparison) doubles the agent runs but provides delta evidence.

**Why:** Budgeting: a 5-eval with-skill pass is ~5 Haiku + 5 Sonnet; decide if --baseline (10 Haiku + 5 Sonnet) is worth the delta signal before committing to the cost.

---
title: Skill Bench evals run on Haiku by design; do not re-run on bigger models
tags: [process, skills, testing]
status: verified 2026-10-30 auto
source: [.claude/skills-bench/run_bench.py, .claude/skills/skill-bench/SKILL.md]
as_of: 993c90ef1 2026-10-30
---
Eval agents run on Haiku exclusively. The regression test's value is proving the skill survives lighter models; re-running a failure on a bigger model defeats the point.

**Why:** Process skills must work reliably on Haiku so they survive deployment on constrained hardware or API limits. Fixing failures by switching models hides brittleness.

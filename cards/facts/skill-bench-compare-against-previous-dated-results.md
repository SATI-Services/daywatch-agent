---
title: Compare Skill Bench results against the previous dated run before trusting the edit
tags: [process, skills, testing]
status: verified 2026-10-30 auto
source: [.claude/skills/skill-bench/SKILL.md]
as_of: 993c90ef1 2026-10-30
---
Results land in `.claude/skills-bench/results/<date>/` (`benchmark.md` plus per-run `grading.json` with evidence). Compare the new run against the previous dated results directory — a pass rate means little without the trend, and the previous dir is the baseline your edit must not regress.

**Why:** Trend is the signal; an absolute pass rate hides what the rate was before the edit.

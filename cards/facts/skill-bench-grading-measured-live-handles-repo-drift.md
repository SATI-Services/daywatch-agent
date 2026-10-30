---
title: Skill Bench grades against live ground truth; repo drift is handled, eval premises can rot
tags: [process, skills, testing]
status: verified 2026-10-30 auto
source: [.claude/skills-bench/run_bench.py, .claude/skills/skill-bench/SKILL.md]
as_of: 993c90ef1 2026-10-30
---
Ground truth is re-measured at grading time via `ground_truth_check` commands, so expected answers changing because the repo changed are handled automatically. Eval *premises* (e.g., docs that were archived) can rot and require `evals.json` updates.

**Why:** Live grading means the benchmark adapts to repo evolution without manual answer-key maintenance. But if an eval references a doc that no longer exists, the eval itself needs updating, not the skill.

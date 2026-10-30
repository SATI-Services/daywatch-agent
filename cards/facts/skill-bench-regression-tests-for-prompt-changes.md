---
title: Run a skill's evals before committing any skill edit — Skill Bench is the only regression test
tags: [process, skills, testing]
status: verified 2026-10-30 auto
source: [.claude/skills/skill-bench/SKILL.md, .claude/skills-bench/run_bench.py]
as_of: 993c90ef1 2026-10-30
---
Skills are prompts with no compiler; the benchmark in `.claude/skills-bench/` is their only regression test. Treat a skill edit like a code change.

- After creating or editing any skill in `.claude/skills/` → run that skill's evals (the `skill` field per eval in `.claude/skills-bench/evals.json`), e.g. `python3 .claude/skills-bench/run_bench.py --evals 0,1`. Do not commit the skill change without them.
- On request ("benchmark the skills") → full run.
- Monthly, or when the repo shifts a lot → full run.

**Why:** An innocuous rewording can stop a lighter model from following the procedure, and nothing tells you until an agent quietly trusts a stale doc again.

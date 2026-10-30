---
title: Skill Bench runner at .claude/skills-bench/run_bench.py is harness-agnostic
tags: [process, skills, testing]
status: verified 2026-10-30 auto
source: [.claude/skills-bench/run_bench.py]
as_of: 993c90ef1 2026-10-30
---
`.claude/skills-bench/run_bench.py` is a harness-agnostic process-skill benchmark runner. Project evals live in `.claude/skills-bench/evals.json`; the runner grades each run against live ground truth and writes a dated results dir at `.claude/skills-bench/results/<YYYY-MM-DD-HHMM>/`.

**Why:** Ground truth is re-measured live at grading time, so repo drift is handled by the shared runner while the evals themselves stay project-specific.

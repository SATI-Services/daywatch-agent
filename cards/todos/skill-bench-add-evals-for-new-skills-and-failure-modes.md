---
title: Add evals to .claude/skills-bench/evals.json for new skills and discovered failure modes
tags: [process, skills, testing]
status: open
owner: unassigned
since: 2026-10-30
source: [.claude/skills/skill-bench/SKILL.md]
as_of: 993c90ef1 2026-10-30
---
When a new skill lands or a failure mode surfaces in the wild (best evals are post-mortems), add a realistic prompt, `ground_truth_check` commands to measure truth at grading time, and assertions phrased against live output rather than frozen answers.

Done = eval is in `evals.json`, passes with-skill on Haiku, and committed together with any skill change that prompted it.

---
title: Write-evals run in throwaway git worktrees and are told never to push
tags: [process, skills, testing]
status: verified 2026-10-30 auto
source: [.claude/skills-bench/run_bench.py, .claude/skills/skill-bench/SKILL.md]
as_of: 5520a2242 2026-10-30
---
Write-evals run in throwaway git worktrees and are instructed never to push. In `run_eval` the agent's cwd is `wt or REPO`: a with-skill run gets a worktree only when the eval sets `isolation`, otherwise it runs in the real checkout. Baseline runs always get a worktree with the skill under test stripped, so they can't crib from it. Worktrees are created detached from `HEAD` and removed with `git worktree remove --force` after the commit info is captured. Agent outputs go to a neutral temp dir and are archived into the results tree afterwards.

**Why:** Safety and cheating: worktree isolation plus the never-push instructions in the prompts are the guardrails, and the runner never touches your checkout's `main`.

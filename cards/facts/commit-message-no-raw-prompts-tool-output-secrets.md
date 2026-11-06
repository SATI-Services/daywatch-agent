---
title: Commit messages carry no raw prompts, tool output, or secrets
tags: [git, secrets, hygiene]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: 20dd42e0f 2026-11-06
---
Nothing sensitive in commit messages — no secrets/tokens, and **no raw model/investigation prompts or tool output** pasted into a subject or body. Write a human summary of *what and why*.

**Why:** A commit message is a permanent public record; pasted prompts and tool output leak internal detail into it.

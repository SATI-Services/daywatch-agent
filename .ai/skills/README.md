# Skills (shared across harnesses)

A skill packages "the right way to do X in this codebase" as a reusable, invokable artifact — so each agent doesn't rediscover it (and get it subtly wrong) every time. Write one whenever you find yourself explaining the same procedure to an agent twice.

> **Two kinds of skill.** This README covers **coding** skills (domain procedures — "how to add a
> feature in this codebase") and how to share them across harnesses. The **process** skills that
> make the delivery workflow itself executable (`session-docs`, `status-reconcile`, `parity-audit`,
> `skill-bench`) live in [`process/`](process/) — install those on every project; they're what let a
> lighter model follow the shared-memory and git discipline deterministically.

## Store once, symlink into each harness

Keep the source of truth in one harness-neutral directory and symlink it into each harness's skills dir, so one copy serves every tool:

```
.ai/skills/<skill-name>/SKILL.md      ← the real file (source of truth)

.claude/skills/<skill-name>  -> ../../.ai/skills/<skill-name>     (symlink)
.agents/skills/<skill-name>  -> ../../.ai/skills/<skill-name>     (symlink)
```

```bash
# create the shared skill, then link it into each harness
mkdir -p .ai/skills/<skill-name> .claude/skills .agents/skills
ln -s ../../.ai/skills/<skill-name> .claude/skills/<skill-name>
ln -s ../../.ai/skills/<skill-name> .agents/skills/<skill-name>
```

## SKILL.md shape

```markdown
---
name: <skill-name>
description: <what it does + when to use it — this is what triggers activation>
when_to_use: <concrete example requests that should activate it>
---

# <Skill title>
Steps, rules, and the non-negotiables. Tell the agent to discover specifics from
live source, never from memory. List what it must NOT do.
```

Mandate activation in the entrypoint: *"activate the relevant skill when you work in that domain — don't wait until you're stuck."*

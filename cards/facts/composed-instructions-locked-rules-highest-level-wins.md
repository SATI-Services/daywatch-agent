---
title: Rule strength decides how the org, aid and project levels combine
tags: [harness, instructions, authority]
status: verified 2026-11-06 auto
source: [.claude/harness/instructions/INSTRUCTIONS.md]
as_of: 20dd42e0f 2026-11-06
---
A rule's `strength` decides how the levels combine. **Locked** rules resolve to the highest level that defines them — `org` (highest for guardrails) > `aid` (middle) > `project` (highest for local facts). **Default** rules resolve to the most specific level; **additive** rules accumulate.

A lower level may not change the body of a locked rule. It may set a declared knob, or replace the body when the rule says `overridable: true`.

**Why:** Guardrails need a single source of truth. Without the level hierarchy, rules drift and contradict each other across harnesses.

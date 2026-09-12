---
title: Wire contract changes are docs-first: the spec lands in the system docs corpus before any emit-side code
tags: [wire-contract, docs, skill]
status: verified 2026-09-12 auto
source: [.claude/skills/daywatch-payloads/SKILL.md]
as_of: f10ffdf64 2026-09-12
---
Wire contract changes are docs-first: the spec change lands in the system docs corpus — `system/agent-protocol.md` (field table, `_group` recipe, `v` bump if needed) and the canonical sample batch `system/samples/records.v1.json` — before any emit-side code is written. The `daywatch-payloads` skill (`.claude/skills/daywatch-payloads/SKILL.md`) documents the procedure.

**Why:** Three components implement the contract independently — this package (producer), the ingest API (consumer/transformer), and the ClickHouse schema (storage) — so the doc changes first and the components fan out, and no emit-side change ships ahead of the spec.

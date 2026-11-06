---
title: Wire contract is cross-repo and docs-first via the daywatch-payloads skill
tags: [wire-contract, docs-first, cross-repo]
status: verified 2026-11-06 auto
source: [docs/WORKFLOW.md]
as_of: c67a73d16 2026-11-06
---
**The wire contract is cross-repo and docs-first** — envelope shapes, the `{len}:v1:{hash}:{json}` framing, and the retry/503 pause contract change only via the `daywatch-payloads` skill against `system/agent-protocol.md`, never package-locally.

**Why:** The wire contract lands on every installed host at their next `composer update`, so it must be stable and coordinated across repos.

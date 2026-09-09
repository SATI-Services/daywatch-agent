---
title: Wire contract changes are docs-first via daywatch-payloads skill, never package-local
tags: [wire-contract, cross-repo, envelope, protocol]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md]
as_of: 16a6c506a 2026-09-06
---
Envelope shapes, the `{len}:v1:{hash}:{json}` framing, and the retry/503 pause contract change only via the `daywatch-payloads` skill against `system/agent-protocol.md`, never package-locally. Record DTO field names ARE the wire contract; renaming one is a cross-repo change.

**Why:** The wire contract couples this package to the central ingest and Java relay across multiple repos. Changes must be coordinated; package-local refactors can silently break downstream.

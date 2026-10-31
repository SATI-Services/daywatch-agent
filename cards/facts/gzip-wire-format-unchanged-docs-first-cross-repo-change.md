---
title: The gzip wire format is unchanged; dropping compression is a cross-repo docs-first change
tags: [compression, wire-contract]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: da814d4f7 2026-10-31
---
The gzip wire format itself is unchanged. Dropping compression would be a cross-repo contract change — docs-first via the `daywatch-payloads` skill — not a package-local one.

**Why:** The wire contract is cross-repo and docs-first; it is never edited package-locally.

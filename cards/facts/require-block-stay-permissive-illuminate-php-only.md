---
title: Only illuminate/support ^11|^12|^13 and php ^8.2 stay permissive in require; never tighten them
tags: [dependencies, composer, host-compat]
status: verified 2026-10-31 auto
source: [AGENTS.md, docs/WORKFLOW.md]
as_of: 41d44b355 2026-10-31
---
Only `illuminate/support ^11|^12|^13` and `php ^8.2` stay permissive in `require`; never tighten them for tooling's sake. These two constraints ARE the package's host-compatibility contract. Everything else in `require` is deliberately locked (the `react/*` daemon deps are pinned tilde-to-lock to their installed minors — patch drift only).

**Why:** Host compatibility is the package's core promise. Tightening these constraints locks out host apps and breaks the adoption model.

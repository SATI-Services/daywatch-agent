---
title: Only illuminate/support ^11|^12|^13 and php ^8.2 stay permissive in require; never tighten them
tags: [dependencies, constraints, stability]
status: verified 2026-09-09 auto
source: [CLAUDE.md, docs/WORKFLOW.md]
as_of: 58f3dad19 2026-09-09
---
In `composer.json`'s `require` block, exactly two constraints stay permissive — `illuminate/support ^11|^12|^13` and `php ^8.2` — because those two ARE the package's host-compatibility contract. They must never be tightened for tooling's sake (dev tooling needing PHP ≥ 8.3 is not a reason to bump the `php` constraint). Everything else in `require` is deliberately locked.

**Why:** These two constraints decide which host apps can install the package; tightening them breaks consumers. `docs/WORKFLOW.md` lists them as a never-tightened invariant and counts the `require` block as critical core.

---
title: Group.normalizeSql() collapses IN and bulk VALUES lists for grouping stability
tags: [wire, grouping]
status: verified 2026-11-11 auto
source: [src/Support/Group.php]
as_of: 43f0e0487 2026-11-11
---
Group.normalizeSql() normalizes SQL for grouping only (transmitted sql field stays raw, bindings never substituted):
- Collapses whitespace
- `in (?, ?, ?)` → `in (?)`
- `values (...), (...), ...` → single tuple

**Why:** Parameter count doesn't fragment groups; raw SQL with placeholders is transmitted unchanged.

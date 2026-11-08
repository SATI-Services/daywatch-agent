---
title: ExceptionRecord _group is xxh128(class|code|file|line)
tags: [wire-contract, exception, records, grouping]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionRecord.php]
as_of: 073ae2e05 2026-11-07
---
ExceptionRecord emits `_group` (the Issue key) as xxh128(class|code|file|line), collapsing stack traces of the same exception type/location into one issue.

**Why:** Identical exceptions at the same line are the same defect; grouping enables issue-oriented alerting.

---

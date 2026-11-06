---
title: KPI meaning lives in instructions, never its number; the value lives in the knowledge layer and pm:kpi
tags: [kpi, measurement, stale]
status: verified 2026-11-06 auto
source: [.claude/harness/instructions/INSTRUCTIONS.md]
as_of: 20dd42e0f 2026-11-06
---
A KPI's **meaning** lives in the instruction channel, never its number. The measured value lives in the knowledge layer (`cards/<deck>/facts/…`, with `as_of` provenance and a staleness rule) and in `php artisan pm:kpi`. A number written into an instruction file is stale as soon as it is read, is paid for by every session, and no mechanism can tell you it rotted.

**Why:** Instructions are loaded by every session, so a number there becomes part of the instruction cost, and there is no mechanism to tell when it drifted from reality.

---

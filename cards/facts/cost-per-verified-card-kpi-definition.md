---
title: Cost per unit of verified work — fact cards pass or fix, excludes healer/profile/auditor
tags: [kpi, metric, cost]
status: verified 2026-11-06 auto
source: [.claude/harness/instructions/INSTRUCTIONS.md]
as_of: 20dd42e0f 2026-11-06
---
Cost per unit of verified work is a KPI. Numerator: `llm_usage.cost_micros` over the window for roles `cutter` and `marker` (whose output the quality half grades). Denominator: distinct FACT cards holding a `pass` or `fix` mark in the window — a `fix` is verified work the marker repaired and re-marked. Companion figure: the marker's pass rate over graded fact cards (graded = `pass|fix|fail`). An `error` mark is not a grading; an `audit` run is not a verdict. Healer, profile and auditor spend belong to other KPIs.

**Why:** Verification is the unit of delivered work. Cost per verified fact is the efficiency of the whole tooling layer.

---

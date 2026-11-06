---
title: KPI states what it cannot see in the artefact that carries its number
tags: [kpi, transparency, blind-spot]
status: verified 2026-11-06 auto
source: [.claude/harness/instructions/INSTRUCTIONS.md]
as_of: 20dd42e0f 2026-11-06
---
Every KPI states **what it cannot see** in the artefact that carries its number, not in a footnote someone has to find. For `org.kpi.cost-per-verified-card`: interactive harness-agent spend is not in `llm_usage` at all (the number is the board's own model spend, not the estate's delivery cost); `cost_micros` is a price-table estimate, not a bill; and a `pass` is model-graded — `verified … auto` means the card's cited sources support it, never that a person read it.
